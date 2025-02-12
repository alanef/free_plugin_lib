<?php
// extract-translations.php

class TranslationExtractor {
	private $strings = [];
	private $sourceFiles = [];
	private $excludeDirs = ['vendor', 'node_modules', 'tests'];
	private $baseDir;
	private $srcDir;

	public function __construct($baseDir) {
		$this->baseDir = rtrim($baseDir, '/');
		$this->srcDir = $this->baseDir . '/../src';
	}

	public function findPHPFiles() {
		if (!is_dir($this->srcDir)) {
			die("Source directory not found: {$this->srcDir}\n");
		}

		$iterator = new RecursiveIteratorIterator(
			new RecursiveDirectoryIterator($this->srcDir)
		);

		foreach ($iterator as $file) {
			if ($file->isFile() && $file->getExtension() === 'php') {
				$relativePath = str_replace($this->srcDir . '/', '', $file->getPathname());

				// Skip excluded directories
				if ($this->shouldSkipFile($relativePath)) {
					continue;
				}

				$this->sourceFiles[] = $file->getPathname();
			}
		}
	}

	private function shouldSkipFile($relativePath) {
		foreach ($this->excludeDirs as $excludeDir) {
			if (strpos($relativePath, $excludeDir . '/') === 0) {
				return true;
			}
		}
		return false;
	}

	public function extractStrings() {
		foreach ($this->sourceFiles as $file) {
			$content = file_get_contents($file);

			// Match $this->translate('string') or $this->translate("string")
			preg_match_all('/\$this->translate\([\'"](.+?)[\'"]\)/', $content, $matches);

			if (!empty($matches[1])) {
				foreach ($matches[1] as $string) {
					// Store file location for reference
					$this->strings[$string][] = $file;
				}
			}
		}
	}

	public function generatePOT($outputFile) {
		// Create languages directory if it doesn't exist
		$languagesDir = dirname($outputFile);
		if (!is_dir($languagesDir)) {
			mkdir($languagesDir, 0755, true);
		}

		$pot_content = 'msgid ""' . "\n";
		$pot_content .= 'msgstr ""' . "\n";
		$pot_content .= '"Project-Id-Version: Free Plugin Library\n"' . "\n";
		$pot_content .= '"POT-Creation-Date: ' . date('Y-m-d H:i:sO') . '\n"' . "\n";
		$pot_content .= '"MIME-Version: 1.0\n"' . "\n";
		$pot_content .= '"Content-Type: text/plain; charset=UTF-8\n"' . "\n";
		$pot_content .= '"Content-Transfer-Encoding: 8bit\n"' . "\n";
		$pot_content .= '"X-Generator: Custom Library Extractor\n"' . "\n";
		$pot_content .= '"X-Domain: free-plugin-lib\n"' . "\n\n";

		foreach ($this->strings as $string => $files) {
			// Add reference comments
			foreach ($files as $file) {
				$relativePath = str_replace($this->srcDir . '/', '', $file);
				$pot_content .= "#: $relativePath\n";
			}

			// Add the translation string
			$pot_content .= 'msgid "' . addcslashes($string, '"\\') . '"' . "\n";
			$pot_content .= 'msgstr ""' . "\n\n";
		}

		file_put_contents($outputFile, $pot_content);

		echo "Found " . count($this->strings) . " unique strings in " . count($this->sourceFiles) . " files.\n";
		echo "POT file generated: $outputFile\n";
	}
}

// Script execution
$baseDir = dirname(__FILE__);
$outputFile = $baseDir . '/../src/languages/free-plugin-lib.pot';

$extractor = new TranslationExtractor($baseDir);
$extractor->findPHPFiles();
$extractor->extractStrings();
$extractor->generatePOT($outputFile);