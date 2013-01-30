#!/bin/sh

curl -d output_info=compiled_code -d compilation_level=SIMPLE_OPTIMIZATIONS -d code_url=https://github.com/defunkt/jquery-pjax/raw/master/jquery.pjax.js http://closure-compiler.appspot.com/compile > redaxo/src/core/assets/jquery-pjax.min.js
curl http://code.jquery.com/jquery-latest.min.js > redaxo/src/core/assets/jquery.min.js
echo "redaxo/src/core/assets/jquery-pjax.min.js\nredaxo/src/core/assets/jquery.min.js" | php coding_standards.phar fix
cp redaxo/src/core/assets/jquery-pjax.min.js assets
cp redaxo/src/core/assets/jquery.min.js assets
