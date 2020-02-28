<?php
namespace AndikaMC\WebOptimizer;

class WebOptimizer extends Hook
{
	protected $Validator, $BenchmarkTime, $HtmlCracker;

	public function __construct($input, $options = [])
	{
		parent::__construct(); // autoload from Hook

		$this->Validator = new Validator;
		$this->input = $input;
		$this->options = $options;

		return $this;
	}

	public function Optimize()
	{
		if (!($this->input = trim((string) $this->input)))
		{
			return $this->input;
		}

		if (!$this->Validator->IsHTMLDoc($this->input))
		{
			return $this->input;
		}

		if (isset($this->options["benchmark"]) && !empty($this->options['benchmark']))
		{
			$this->BenchmarkTime = microtime(true);
		}

		$html = $this->input;
		$html = $this->OptimizeHTML($html, $this->options);
		$this->input = $html;

		/**
		 * 
		 */
		if (isset($this->options["benchmark"]) && !empty($this->options['benchmark']))
		{
			$this->BenchmarkTime = number_format(microtime(true) - $this->BenchmarkTime, 5, '.', '');

			$this->input .= "\n\n";

			if ($this->Validator->IsValidUTF8($this->input))
			{
				$this->input .= "<!-- Application Optimization took " . htmlspecialchars($this->BenchmarkTime, ENT_NOQUOTES, 'UTF-8') . " seconds to process -->";
			}	
		}

		return $this->input;
	}

}