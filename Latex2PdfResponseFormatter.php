<?php
/**
 *
 * @author Marco Petrini <marco@bhima.eu>
 * @created 20/02/19 12:35 PM
 */

namespace pcrt\latex;

use Yii;
use yii\base\Component;
use yii\web\Response;
use yii\web\ResponseFormatterInterface;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;

/**
 * Latex2PdfResponseFormatter formats the given Latex into a PDF response content.
 *
 * It is used by [[Response]] to format response data.
 *
 * @author Marco Petrini <marco@bhima.eu>
 * @since 2.0
 */
class Latex2PdfResponseFormatter extends Component implements ResponseFormatterInterface
{

	public $latexbin = "/usr/local/bin/pdflatex";
	public $build_path = "";
	public $timeout = 120;
	public $idletimeout = 60;

	public $options = [];

	/**
	 * @var Closure function($mpdf, $data){}
	 */
	public $beforeRender;

	/**
	 * Formats the specified response.
	 *
	 * @param Response $response the response to be formatted.
	 */
	public function format($response)
	{
		$response->getHeaders()->set('Content-Type', 'application/pdf');
		$response->content = $this->formatPdf($response);
	}

	/**
	 * Formats response Latex in PDF
	 *
	 * @param Response $response
	 */
	protected function formatPdf($response)
	{

		// #TODO: Implement BeforeRender Functionality

		$tmpfile_name = uniqid();
		if($this->build_path == ""){
			$this->build_path = getcwd().DIRECTORY_SEPARATOR;
		}

		$tmpfile_path = realpath($this->build_path.DIRECTORY_SEPARATOR.$tmpfile_name);
		$logfile_path = realpath($this->build_path.DIRECTORY_SEPARATOR.$tmpfile_name.".log");
		$auxfile_path = realpath($this->build_path.DIRECTORY_SEPARATOR.$tmpfile_name.".aux");
		$pdffile_path = realpath($this->build_path.DIRECTORY_SEPARATOR.$tmpfile_name.".pdf");

		// Write temp file
		$fp = fopen($tmpfile_path, 'w+');
		fwrite($fp, $response->data);
		fclose($fp);

		// Start Process
		$process = new Process(
			[
				$this->latexbin,
				'-interaction=nonstopmode',
				$tmpfile_path,
				'-output-directory=$build_path'
			]
		);

		$process->setTimeout($this->timeout);
		$process->setIdleTimeout($this->idletimeout);
		$process->run();

		if (!$process->isSuccessful()) {
			@unlink($logfile_path);
			@unlink($auxfile_path);
			@unlink($tmpfile_path);
    	throw new ProcessFailedException($process);
		}else{
			@unlink($logfile_path);
			@unlink($auxfile_path);
			@unlink($tmpfile_path);
			if(file_exists($pdffile_path)){
				$pdf = file_get_contents($pdffile_path);
				unlink($pdffile_path);
				return $pdf;
			}
		}
	}
}
