<?php
	reason_include_once("classes/media/factory.php");
	reason_include_once("classes/embedded_tag.php");

	class DefaultEmbedHandler {
		protected $parsedTags;

		// make sure you define tags as uppercase in this array. They can be input by users in mixed-case, but they need to be
		// uppercase in this definition.
		protected $embeddableTypes = Array(
			"IMG" => Array(
				"reason_type" => "image",
				"rel" => "news_to_image",
				"fetcher" => "getAssociatedImageEntities",
				"handler" => "handleImageSubstitution"
			),
			"MEDIA" => Array(
				"reason_type" => "av",
				"rel" => "news_to_media_work",
				"fetcher" => "getAssociatedMediaEntities",
				"handler" => "handleMediaSubstitution"
			),
		);

		protected $internalCounters = Array();
		protected $associatedEntities = Array();
		protected $siteId;

		function __construct($siteId) {
			$this->siteId = $siteId;
			foreach ($this->embeddableTypes as $typeCode => $typeConfig) {
				$this->internalCounters[$typeCode] = 0;
				$this->associatedEntities[$typeCode] = null;
			}
		}

		protected function getCounter($counterType) {
			if (isset($this->internalCounters[$counterType])) {
				return $this->internalCounters[$counterType];
			} else {
				trigger_error("$counterType is not a valid counter type for a NewsportalStoryRenderer");
				return -1;
			}
		}
		
		// given a key in embeddableTypes, get the current value stored in internalCounters and then bump it for the next caller
		private function getCounterAndIncrement($counterType) {
			if (isset($this->internalCounters[$counterType])) {
				$rv = $this->internalCounters[$counterType];
				$this->internalCounters[$counterType] = $rv + 1;
				return $rv;
			} else {
				trigger_error("$counterType is not a valid counter type for a NewsportalStoryRenderer");
				return -1;
			}
		}
		
		// searches the supplied content for [[ IMG ]], [[ ID=x ]], etc. style tags, and returns info about them - their type, their "x" if any, and their exact string (allows for whitespace/capitalization mismatch).
		protected function findAllTags($content) {
			// method 1 - simpler approach before we allowed params.
			// $numFound = preg_match_all("/\[\[\s*(\w*)\s*=*\s*(\d*)\s*\]\]/i", $content, $matches);
			// return $numFound == 0 ? Array() : $matches;
			// $numFound = preg_match_all("/\[\[\s*(\w*)\s*=*\s*(\d*)(?:\s+(\w+)\s*=\s*\"([^\"]+)\"\s*)*\]\]/i", $content, $matches);
			
			// method 2 - backup
			// $numTagsFound = preg_match_all("/\[\[\s*(\w+)(?:\s*=\s*\"(\w*)\")?([^\[]*)\]\]/", $content, $firstPassMatches);
			// $numParamsFound = preg_match_all("/\s*(\w+)\s*=\s*\"([^\"\\\\]*(?:\\\\.[^\"\\\\]*)*)\"/", $rawParams, $secondPassMatches);
			
			// *************** EXPLANATION *******************
			// tags look something like this, I'll use this in below examples:
			// [[ TAG ]]
			// [[ TAG(="foo") (value="x") (otherValue="y") ]]
			// etc.
			//
			// double quotes are required around ALL values. Might be nice to make them
			// optional at times but these are already some obscenely hard to read regexes.
			//
			// innerQuoteAllowEscapes is a partial regex that allows you to have escaped \" quotes
			// within a quote delimited string. think allowing either "hulk hogan" or
			// "jesse \"the body\" ventura" as valid strings. See 
			// http://stackoverflow.com/questions/5695240/php-regex-to-ignore-escaped-quotes-within-quotes
			// for details, or Friedl's Mastering Regular Expressions.
			//
			// Beyond that, we take two passes -- tagRegex is used to grab the tag name and the optional value;
			// so "TAG" and "foo" as above. It also grabs everything beyond that, if anything; so like the
			// 'value="x" otherValue="y"' portion of the above tag, let's call that "rawParams".
			//
			// Then, if rawParams actually contained something, we run that through paramRegex. That parses out
			// and separates "value"/"x", "otherValue"/"y".
			//
			// A lot of the complexity of these regexes is around things being optional - you can legally have either
			// [[ TAG ]] or [[ TAG="x" ]] for instance, you can have whitespace so [[TAG = "foo" ]] is ok, and so on.
			//
			// FINAL RESULT - you'll get back an array of EmbeddedTag objects.
			$innerQuoteAllowEscapes = "([^\"\\\\]*(?:\\\\.[^\"\\\\]*)*)";
			// $tagRegex = "/\[\[\s*(\w+)(?:\s*=\s*\"$innerQuoteAllowEscapes\")?([^\[]*)\]\]/";
			$tagRegex = "/\[\[\s*(\w+)(?:\s*=\s*\"(\d+)\")?([^\[]*)\]\]/";
			$paramRegex = "/\s*(\w+)\s*=\s*\"$innerQuoteAllowEscapes\"/";

			$numTagsFound = preg_match_all($tagRegex, $content, $firstPassMatches);
			
			$rv = Array();
			for ($i = 0 ; $i < $numTagsFound ; $i++) {
				$rawParams = trim($firstPassMatches[3][$i]);

				$et = new EmbeddedTag($firstPassMatches[0][$i],
										$firstPassMatches[1][$i],
										$firstPassMatches[2][$i]);

				// $paramData = Array();
				if (!empty($rawParams)) {
					$numParamsFound = preg_match_all($paramRegex, $rawParams, $secondPassMatches);
					for ($k = 0 ; $k < $numParamsFound ; $k++) {
						// $paramData[$secondPassMatches[1][$k]] = $secondPassMatches[2][$k];
						$et->addParam($secondPassMatches[1][$k], $secondPassMatches[2][$k]);
					}
				}

				$rv[] = $et;
			}

			// echo "<PRE>"; print_r($firstPassMatches); echo "</PRE>";
			// echo "<PRE>"; print_r($rv); echo "</PRE>";
			// echo(implode($rv, "<br>")) . "<hr>";
			// return Array();
			return $rv;

			// this one captures tag name, tag id, optional params (all lumped together).
			// \[\[\s*(\w*)(?:\s*=\s*\"(\w*)\")*(.*)\]\]

			// given lump data, this one seems to handle even escaped quotes
			// \s*(\w+)\s*=\s*\"([^\"\\]*(?:\\.[^\"\\]*)*)\"

			// (partway there) given lump data, this one almost does everything (but doesn't allow you to include an escaped quote)
			// \s*(\w+)\s*=\s*\"([^\"]+)\"
		}

		protected function getAssociatedImageEntities($newsPost) { return $this->getAssociatedEntities($newsPost, "IMG"); }
		protected function getAssociatedMediaEntities($newsPost) { return $this->getAssociatedEntities($newsPost, "MEDIA"); }

		// returns, fetching if necessary, entities of a particular type (IMG/image, MEDIA/av, etc.) that are associated with this news/post.
		protected function getAssociatedEntities($newsPost, $entityCode) {
			if (! isset($this->associatedEntities[$entityCode])) {
				$reasonType = $this->embeddableTypes[$entityCode]["reason_type"];
				$rel = $this->embeddableTypes[$entityCode]["rel"];
				// echo "fetching [$entityCode] entities; work with [$reasonType], [$rel]<P>";

				// $es = new entity_selector($this->siteId);
				// $es = new entity_selector($newsPost->get_owner()->id());
				$es = new entity_selector();
				$es->set_env('site', $this->siteId);
				$es->description = "fetching associated entities of type \"" . $reasonType . "\" for news/post " . $newsPost->id();
				$es->add_type(id_of($reasonType));

				$es->add_right_relationship($newsPost->id(), relationship_id_of($rel));

				$es->set_order('rel_sort_order ASC');

				$this->associatedEntities[$entityCode] = $es->run_one();

				// echo "<PRE>"; var_dump($this->associatedEntities); echo "</PRE>";
			}
			return $this->associatedEntities[$entityCode];
		}

		private function getAvailableEntity($newsPostContext, $entityId) {
			$probe = new entity($entityId);

			if ($probe->owned_or_borrowed_by($this->siteId) || $probe->owned_or_borrowed_by($newsPostContext->get_owner()->id())) {
				return $probe;
			} else {
				return null;
			}

			// TODO - look into either using entity::owned_or_borrowed_by(<SITE_ID>)
			// or util.php::site_owns_entity / util::site_borrows_entity as possible optimization

			// why not stop here? b/c this site maybe doesn't have access to it. We don't want content
			// creators to be able to bypass Reason permissions and borrowing rules and just pull in any old content they feel like.
			//
			// solution for now is to run a check using the entity selector to ensure this site owns/borrows the content we're referring to.
			// there is probably a better way to do this? possible area for optimization. Might not be so bad as I think the entity_selector system
			// is caching results anyway so this is hopefully not too terrible in terms of incurring needless extra db hits.
			//
			// $probe->get_values();
			// var_dump("<PRE>", $probe, "</PRE>");

			// $es = new entity_selector($this->siteId);

			/*
			$es->add_type($probe->get_value("type"));
			$es->add_relation("entity.id = " . $entityId);
			$entities = $es->run_one();

			if (count($entities) == 1) {
				return $probe;
			} else {
				return null;
			}
			 */
		}

		function processEmbeddedContent($newsPost, $fieldName = "content") {
			// echo "default EH processEmbeddedContent firing...<P>";
			$fieldContents = $newsPost->get_value($fieldName);

			$this->parsedTags = $this->findAllTags($fieldContents);

			foreach ($this->parsedTags as $et) { // EmbeddedTag
				// echo "processing [" . $et . "]...<P>";
				$tagContent = $et->originalText;
				$tagType = $et->tag;
				$tagVal = $et->tagValue; // might contain the idx of the associated image, or an absolute id of an entity

				$doReplace = false;
				$replacement = "";

				if (isset($this->embeddableTypes[$tagType])) {
					$fetcher = $this->embeddableTypes[$tagType]["fetcher"];
					$handler = $this->embeddableTypes[$tagType]["handler"];

					$associatedEntities = $this->$fetcher($newsPost);

					if ($tagVal == "") { // tag was style [[ IMG ]], [[ MeDiA]], etc.
						$pos = $this->getCounterAndIncrement($tagType);
					} else { // tag was style [[ IMG=2 ]], [[ media=1]]. etc.
						$pos = intval($tagVal) - 1;
					}

					if ($pos < count($associatedEntities)) {
						$sliced = array_slice($associatedEntities, $pos, 1);
						$replacement = $this->$handler($pos, $sliced[0], $et);
					} else {
						$replacement = "<!-- " . $tagType ." with sequence $pos exceeds associated entity array -->";
					}
					$doReplace = true;
				} else if ($tagType == "ID") {
					$entityId = intval($tagVal);
					$e = $this->getAvailableEntity($newsPost, $entityId);

					if ($e != null) {
						$entityType = $e->get_value("type");

						foreach ($this->embeddableTypes as $typeCode => $typeConfig) {
							if (id_of($typeConfig["reason_type"]) == $entityType) {
								$handler = $typeConfig["handler"];
								$replacement = $this->$handler($this->getCounterAndIncrement($typeCode), $e, $et);
							}
						}
					} else {
						$replacement = "<!-- entity with id $entityId not found or not available for this site -->";
					}
					$doReplace = true;
				} else {
					// unsupported tag - do nothing
					$doReplace = false;
				}

				if ($doReplace) {
					$fieldContents = preg_replace("/" . preg_quote($tagContent) . "/", $replacement, $fieldContents, 1);
				}
			}
			/*
			if (count($parsedTags) > 0) {
				$tagContents = $parsedTags[0];
				$tagTypes = $parsedTags[1];
				$tagProps = $parsedTags[2];

				for ($i = 0 ; $i < count($tagContents) ; $i++) {
					$tagContent = $tagContents[$i];
					$tagType = strtoupper($tagTypes[$i]);
					$tagProp = $tagProps[$i]; // might contain the idx of the associated image, or an absolute id of an entity

					$doReplace = false;
					$replacement = "";

					if (isset($this->embeddableTypes[$tagType])) {
						$fetcher = $this->embeddableTypes[$tagType]["fetcher"];
						$handler = $this->embeddableTypes[$tagType]["handler"];

						$associatedEntities = $this->$fetcher($newsPost);

						if ($tagProp == "") { // tag was style [[ IMG ]], [[ MeDiA]], etc.
							$pos = $this->getCounterAndIncrement($tagType);
						} else { // tag was style [[ IMG=2 ]], [[ media=1]]. etc.
							$pos = intval($tagProp) - 1;
						}

						if ($pos < count($associatedEntities)) {
							$sliced = array_slice($associatedEntities, $pos, 1);
							$replacement = $this->$handler($pos, $sliced[0]);
						} else {
							$replacement = "<!-- " . $tagType ." with sequence $pos exceeds associated entity array -->";
						}
						$doReplace = true;
					} else if ($tagType == "ID") {
						$entityId = intval($tagProp);
						$e = $this->getAvailableEntity($newsPost, $entityId);

						if ($e != null) {
							$entityType = $e->get_value("type");

							foreach ($this->embeddableTypes as $typeCode => $typeConfig) {
								if (id_of($typeConfig["reason_type"]) == $entityType) {
									$handler = $typeConfig["handler"];
									$replacement = $this->$handler($this->getCounterAndIncrement($typeCode), $e);
								}
							}
						} else {
							$replacement = "<!-- entity with id $entityId not found or not available for this site -->";
						}
						$doReplace = true;
					} else {
						// unsupported tag - do nothing
						$doReplace = false;
					}

					if ($doReplace) {
						$fieldContents = preg_replace("/" . preg_quote($tagContent) . "/", $replacement, $fieldContents, 1);
					}
				}
			}
			 */

			return $fieldContents;
		}

		public function customInit($publicationModule, $markupGenerator, $item) {
			// echo "default EH customInit firing...<P>";
		}

		public function customPostProcess($publicationModule, $markupGenerator, $item) {
			// echo "default EH customPostProcess firing...<P>";
		}

		protected function handleImageSubstitution($position, $img, $et) {
			return "<!-- img with sequence $position, id " . $img->id() . " -->";
		}

		protected function handleMediaSubstitution($position, $media, $et) {
			return "<!-- audio/video with sequence $position, name " . $media->get_value("name") . " -->";
		}

	}
?>
