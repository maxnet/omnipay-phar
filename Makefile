omnipay.phar: build/artifacts/Burgomaster.php
	php -dphar.readonly=0 build/packager.php

clean:
	rm -rf omnipay.phar omnipay-psr0.zip build/artifacts/staging

build/artifacts/Burgomaster.php:
	mkdir -p build/artifacts
	curl -s https://raw.githubusercontent.com/mtdowling/Burgomaster/0.0.2/src/Burgomaster.php > build/artifacts/Burgomaster.php
