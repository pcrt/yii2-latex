Yii2-Latex
========

Latex2PDF formatter for Yii2 .

This extension "format" Latex responses to PDF files (by default Yii2 includes HTML, JSON and XML formatters). Great for reports in PDF format using Latex views/layouts.

##Installation

The preferred way to install this extension is through [composer](http://getcomposer.org/download/).

Either run

```
$ php composer.phar require pcrt/yii2-latex "*"
```

or add

```
"pcrt/yii2-pdf": "*"
```

to the require section of your `composer.json` file.

## Usage

Once the extension is installed, modify your application configuration to include:

```php
return [
	'components' => [
		...
		'response' => [
			'formatters' => [
				'pdf' => [
					'class' => 'pcrt\latex\Latex2PdfResponseFormatter',
					'latexbin' => '/usr/local/bin/latex',
					'buildpath' => '/folder/you/prefer'
 				],
			]
		],
		...
	],
];
```
For default the buildpath variable is set on your @webroot folder . 


In the controller:

```php

class MyController extends Controller {
	public function actionPdf(){
		Yii::$app->response->format = 'latex';
		$this->layout = '//print'; // A siple template without any html code
		return $this->render('myview', []);
	}
}

```

## License

Yii2-Latex is released under the BSD-3 License. See the bundled `LICENSE.md` for details.


# Useful URLs

* [Latex Manual](https://www.latex-project.org/)

Enjoy!
