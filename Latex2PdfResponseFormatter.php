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
	public $keepfile = false;
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
 * This function is to replace PHP's extremely buggy realpath().
 * @param string The original path, can be relative etc.
 * @return string The resolved path, it might not exist.
 */
	function truepath($path){
	    // whether $path is unix or not
	    $unipath=strlen($path)==0 || $path{0}!='/';
	    // attempts to detect if path is relative in which case, add cwd
	    if(strpos($path,':')===false && $unipath)
	        $path=getcwd().DIRECTORY_SEPARATOR.$path;
	    // resolve path parts (single dot, double dot and double delimiters)
	    $path = str_replace(array('/', '\\'), DIRECTORY_SEPARATOR, $path);
	    $parts = array_filter(explode(DIRECTORY_SEPARATOR, $path), 'strlen');
	    $absolutes = array();
	    foreach ($parts as $part) {
	        if ('.'  == $part) continue;
	        if ('..' == $part) {
	            array_pop($absolutes);
	        } else {
	            $absolutes[] = $part;
	        }
	    }
	    $path=implode(DIRECTORY_SEPARATOR, $absolutes);
	    // resolve any symlinks
	    if(file_exists($path) && linkinfo($path)>0)$path=readlink($path);
	    // put initial separator that could have been lost
	    $path=!$unipath ? '/'.$path : $path;
	    return $path;
	}

	/**
	 * Formats response Latex in PDF
	 *
	 * @param Response $response
	 */
	protected function formatPdf($response)
	{
		$tmpfile_name = uniqid();
		if(!file_exists($this->build_path)){
			$this->build_path = getcwd().DIRECTORY_SEPARATOR;
		}

		$tmpfile_path = $this->truepath($this->build_path.DIRECTORY_SEPARATOR.$tmpfile_name);
		$logfile_path = $this->truepath($this->build_path.DIRECTORY_SEPARATOR.$tmpfile_name.".log");
		$auxfile_path = $this->truepath($this->build_path.DIRECTORY_SEPARATOR.$tmpfile_name.".aux");
		$pdffile_path = $this->truepath($this->build_path.DIRECTORY_SEPARATOR.$tmpfile_name.".pdf");

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
				'-output-directory='.$this->build_path
			]
		);
		
		\Yii::trace("EXEC: " . $this->latexbin . " -interaction=nonstopmode " . $tmpfile_path . ' -output-directory=' . $this->build_path);

		$process->setTimeout($this->timeout);
		$process->setIdleTimeout($this->idletimeout);
		$process->run();

		if (!$process->isSuccessful()) {
			if(!$this->keepfile){
				@unlink($logfile_path);
				@unlink($auxfile_path);
				@unlink($tmpfile_path);
			}
    	throw new ProcessFailedException($process);
		}else{
			if(!$this->keepfile){
				@unlink($logfile_path);
				@unlink($auxfile_path);
				@unlink($tmpfile_path);
			}
			if(file_exists($pdffile_path)){
				$pdf = file_get_contents($pdffile_path);
				if(!$this->keepfile){
					unlink($pdffile_path);
				}
				return $pdf;
			}
		}
	}
}
