VERSION := 1.0.0
PLUGINSLUG := kafkai-wp
SRCPATH := $(shell pwd)/src

bin/linux/amd64/github-release:
	wget https://github.com/aktau/github-release/releases/download/v0.7.2/linux-amd64-github-release.tar.bz2
	tar -xvf linux-amd64-github-release.tar.bz2
	chmod +x bin/linux/amd64/github-release
	rm linux-amd64-github-release.tar.bz2

install: vendor
vendor: src/vendor
	composer install --dev
	composer dump-autoload -a

clover.xml: vendor test

unit: test

test: vendor
	bin/phpunit --coverage-html=./reports

src/vendor:
	cd src && composer install
	cd src && composer dump-autoload -a

build: install
	sed -i "s/@##VERSION##@/${VERSION}/" src/$(PLUGINSLUG).php
	sed -i "s/@##VERSION##@/${VERSION}/" src/inc/Config.php
	mkdir -p build
	rm -rf src/vendor
	cd src && composer install --no-dev
	cd src && composer dump-autoload -a
	cp -ar $(SRCPATH) $(PLUGINSLUG)
	zip -r $(PLUGINSLUG).zip $(PLUGINSLUG)
	rm -rf $(PLUGINSLUG)
	mv $(PLUGINSLUG).zip build/
	sed -i "s/${VERSION}/@##VERSION##@/" src/$(PLUGINSLUG).php
	sed -i "s/${VERSION}/@##VERSION##@/" src/inc/Config.php

dist: install
	mkdir -p dist
	rm -rf src/vendor
	cd src && composer install --no-dev
	cd src && composer dump-autoload -a
	cp -r $(SRCPATH)/. dist/

publish: build bin/linux/amd64/github-release
	bin/linux/amd64/github-release upload \
		--user akshitsethi \
		--repo $(PLUGINSLUG) \
		--tag "v$(VERSION)" \
		--name $(PLUGINSLUG)-$(VERSION).zip \
		--file build/$(PLUGINSLUG).zip

release:
	git stash
	git fetch -p
	git checkout master
	git pull -r
	git tag v$(VERSION)
	git push origin v$(VERSION)
	git pull -r

fmt: install
	bin/phpcbf --standard=WordPress src --ignore=src/vendor,src/assets

lint: install
	bin/phpcs --standard=WordPress src --ignore=src/vendor,src/assets

psr: src/vendor
	composer dump-autoload -o
	cd src && composer dump-autoload -o

i18n: src/vendor
	wp i18n make-pot src/inc src/i18n/$(PLUGINSLUG).pot

cover: vendor
	bin/coverage-check clover.xml 100

clean:
	rm -rf vendor/ src/vendor/
