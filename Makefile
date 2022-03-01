VERSION := 1.5.7
PLUGINSLUG := kafkai
SRCPATH := $(shell pwd)/src

install: vendor
vendor: src/vendor
	composer install
	composer dump-autoload -o

clover.xml: vendor test

update_version:
	sed -i "s/@##VERSION##@/${VERSION}/" src/$(PLUGINSLUG).php
	sed -i "s/@##VERSION##@/${VERSION}/" src/inc/Config.php
	sed -i "s/@##VERSION##@/${VERSION}/" src/i18n/$(PLUGINSLUG).pot

remove_version:
	sed -i "s/${VERSION}/@##VERSION##@/" src/$(PLUGINSLUG).php
	sed -i "s/${VERSION}/@##VERSION##@/" src/inc/Config.php
	sed -i "s/${VERSION}/@##VERSION##@/" src/i18n/$(PLUGINSLUG).pot

unit: test

test: vendor
	bin/phpunit --coverage-html=./reports

src/vendor:
	cd src && composer install
	cd src && composer dump-autoload -o

build: install update_version
	mkdir -p build
	rm -rf src/vendor
	cd src && composer install --no-dev
	cd src && composer dump-autoload -o
	cp -ar $(SRCPATH) $(PLUGINSLUG)
	zip -r $(PLUGINSLUG).zip $(PLUGINSLUG)
	rm -rf $(PLUGINSLUG)
	mv $(PLUGINSLUG).zip build/
	make remove_version

dist: install update_version
	mkdir -p dist
	rm -rf src/vendor
	cd src && composer install --no-dev
	cd src && composer dump-autoload -o
	cp -r $(SRCPATH)/. dist/
	make remove_version

release:
	git stash
	git fetch -p
	git checkout master
	git pull -r
	git tag v$(VERSION)
	git push origin v$(VERSION)
	git pull -r

fmt: install
	composer fmt:wpcs

lint: install
	composer lint:wpcs

psr: src/vendor
	composer dump-autoload -o
	cd src && composer dump-autoload -o

i18n: src/vendor
	bin/wp i18n make-pot src "src/i18n/${PLUGINSLUG}.pot" --slug=$(PLUGINSLUG) --skip-js --exclude=vendor

cover: vendor
	bin/coverage-check clover.xml 80

clean:
	rm -rf vendor/ src/vendor/
