<?php
namespace App\Services;



use voku\helper\AntiXSS;
use App\Interfaces\SanitizerInterface;

class XssCleanService implements SanitizerInterface
{
    protected AntiXSS $antiXSS;
    public function __construct()
    {
        $this->antiXSS = new AntiXSS();
    }
    public function clean(string|null $value):string|null
    {
        return $this->antiXSS->xss_clean($value);
    }
    public function cleanArray(array $data): array
    {
        array_walk_recursive($data, function (&$value) {
            $value = $this->clean($value);
            $value = $this->antiXSS->isXssFound()? '': $value;
        });
        return $data;
    }
}
