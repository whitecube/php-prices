<?php

namespace Whitecube\Price\Formatting;

use Closure;

class CustomFormatter extends Formatter
{
    /**
     * The defined name for this formatter
     */
    protected ?string $name = null;

    /**
     * The custom formatter function
     */
    protected ?Closure $closure = null;

    /**
     * Create a new formatter instance
     */
    public function __construct(?callable $closure = null)
    {
        if (! is_null($closure)) {
            $this->closure = Closure::fromCallable($closure);
        }

        return $this;
    }

    /**
     * Set ther formatter's name
     */
    public function name(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Check if this formatter has the provided name
     */
    public function is(?string $name = null): bool
    {
        if (is_null($name)) {
            return is_null($this->name);
        }

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
