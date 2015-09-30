The delete module is another fairly simple module which handles the deleting, undeleting and expunging processes.  If the page is passed a variable called "undelete", the page simply sets the state to live and returns to the sessioned lister page.  This is perhaps not the best thing to do, since it may set an entity to live without checking for all the required things about an entity.  However, it should be ok.  If an entity was live before it was deleted, it should have all the required fields and relationships and such, so it will be fine.  If it is pending or archived, attempting to delete that entity will actually delete it from the database, rather than setting the state as deleted. 

If the undelete flag is not set, it creates a new class which is an extension of Disco called deleteDisco, or and extension of deleteDisco, if the field 'custom_deleter' is set in reason. 

The deleteDisco class is also pretty straight forward.  It grabs all request vars and puts them into hidden fields, asks the user if they want to delete/expunge the entity and then does the appropriate action in the finish state.  If the user selects "Cancel", it simply sends them back to the lister page.

