<?php
namespace AndikaMC\WebOptimizer;

class Hook
{
    private const __CLASSES_DIR__ = __DIR__ . "/";

    public function __construct()
    {
        spl_autoload_register([$this, "AutoloadClasses"]);
    }

    private function AutoloadClasses($class)
    {
        $class_act = realpath(self::__CLASSES_DIR__).str_replace(__NAMESPACE__, NULL, $class).".php";
        if (is_readable($class_act))
        {
            require_once $class_act;
        }
    }

    protected function OptimizeHTML($buffer, $options)
    {
        $engine = (new Classes\Optimization\Engine)->Optimize($buffer, $options);

        return $engine;
    }
}