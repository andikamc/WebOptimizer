<?php
namespace AndikaMC\WebOptimizer;

class WebOptimizer extends Hook
{
	protected $Validator, $BenchmarkTime, $HtmlCracker;

	public function __construct()
	{
		$this->Validator = new Validator;
	}

	public function Optimize($input, $options = [])
	{
		$this->options = $options;

		if (!($input = trim((string) $input)))
		{
			return $input;
		}

		if (!$this->Validator->IsHTMLDoc($input))
		{
			return $input;
		}

		if (isset($this->options["benchmark"]) && !empty($this->options['benchmark']))
		{
			$this->BenchmarkTime = microtime(true);
		}

		/**
		 * 
		 */
		$input = $this->CompressCombineHeadBodyCss($input);

		/**
		 * 
		 */
		if (isset($this->options["benchmark"]) && !empty($this->options['benchmark']))
		{
			$this->BenchmarkTime = number_format(microtime(true) - $this->BenchmarkTime, 5, '.', '');

			$input .= "\n\n";

			if ($this->Validator->IsValidUTF8($input))
			{
				$input .= "<!-- Application took " . htmlspecialchars($this->BenchmarkTime, ENT_NOQUOTES, 'UTF-8') . " seconds -->";
			}	
		}

		return $input;
	}

	protected function CompressCombineHeadBodyCss($html)
	{
		$this->HtmlCracker = new Functions\HtmlCracker($html, [
			"compress_css" => true,
			"combine_css"  => true,
		]);

		$parsed_html = $this->HtmlCracker->CrackHtml();

		return $parsed_html;
	}
}