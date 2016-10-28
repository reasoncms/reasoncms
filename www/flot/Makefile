# Makefile for generating minified files

.PHONY: all

# we cheat and process all .js files instead of an exhaustive list
all: $(patsubst %.js,%.min.js,$(filter-out %.min.js,$(wildcard *.js)))

%.min.js: %.js
	yui-compressor $< -o $@
<<<<<<< HEAD
=======

test:
	./node_modules/.bin/jshint *jquery.flot.js
>>>>>>> fa0b0cc49183af587a4a17ce547e5c81d0352128
