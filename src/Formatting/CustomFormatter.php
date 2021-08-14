<?php

namespace Whitecube\Price\Formatting;

class CustomFormatter extends Formatter
{
    /**
     * The defined name for this formatter
     *
     * @var null|string
     */
    protected $name;

    /**
     * The custom formatter function 
     *
     * @var callable
     */
    protected $closure;

    /**
     * Create a new formatter instance
     *
     * @param callable $closure
     * @return void
     */
    public function __construct(callable $closure = null)
    {
        $this->closure = $closure;

        return $this;
    }

    /**
     * Set ther formatter's name
     *
     * @param string $name
     * @return this
     */
    public function name(string $name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Check if this formatter has the provided name
     *
     * @param null|string $name
     * @return bool
     */
    public function is($name = null)
    {
        return strtolower($name) === strtolower($this->name);
    }

    /**
     * Run the formatter using the provided arguments
     *
     * @param array $arguments
     * @return null|string
     */
    public function call(array $arguments) : ?string
    {
        return call_user_func_array($this->closure, $arguments);
    }
}
