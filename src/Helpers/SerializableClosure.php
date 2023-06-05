<?php

/*
 * This file is part of the php-resque package.
 *
 * (c) Michael Haynes <mike@mjphaynes.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Resque\Helpers;

/**
 * Serializes job closure
 *
 * @package Resque
 * @author Michael Haynes <mike@mjphaynes.com>
 */
final class SerializableClosure implements \Serializable
{
    /**
     * The Closure instance.
     *
     * @var \Closure
     */
    protected $closure;

    /**
     * The ReflectionFunction instance of the Closure.
     *
     * @var \ReflectionFunction
     */
    protected $reflection;

    /**
     * The code contained by the Closure.
     *
     * @var string
     */
    protected $code;

    /**
     * Create a new serializable Closure instance.
     *
     * @param \Closure $closure
     */
    public function __construct(\Closure $closure)
    {
        $this->closure = $closure;

        $this->reflection = new \ReflectionFunction($closure);
    }

    /**
     * Get the unserialized Closure instance.
     *
     * @return \Closure
     */
    public function getClosure(): \Closure
    {
        return $this->closure;
    }

    /**
     * Get the code for the Closure.
     *
     * @return string
     */
    public function getCode(): string
    {
        return $this->code ?: $this->code = $this->getCodeFromFile();
    }

    /**
     * Extract the code from the Closure's file.
     *
     * @return string
     */
    protected function getCodeFromFile(): string
    {
        $file = $this->getFile();

        $code = '';

        // Next, we will just loop through the lines of the file until we get to the end
        // of the Closure. Then, we will return the complete contents of this Closure
        // so it can be serialized with these variables and stored for later usage.
        while ($file->key() < $this->reflection->getEndLine()) {
            $code .= $file->current();
            $file->next();
        }

        preg_match('/function\s*\(/', $code, $matches, PREG_OFFSET_CAPTURE);
        $begin = $matches[0][1] ?? 0;

        return substr($code, $begin, strrpos($code, '}') - $begin + 1);
    }

    /**
     * Get an SplObjectFile object at the starting line of the Closure.
     *
     * @return \SplFileObject
     */
    protected function getFile(): \SplFileObject
    {
        $file = new \SplFileObject($this->reflection->getFileName());

        $file->seek($this->reflection->getStartLine() - 1);

        return $file;
    }

    /**
     * Get the variables used by the Closure.
     *
     * @return array
     */
    public function getVariables(): array
    {
        if (!$this->getUseIndex()) {
            return [];
        }

        $staticVariables = $this->reflection->getStaticVariables();

        // When looping through the variables, we will only take the variables that are
        // specified in the use clause, and will not take any other static variables
        // that may be used by the Closures, allowing this to re-create its state.
        $usedVariables = [];

        foreach ($this->getUseClauseVariables() as $variable) {
            $variable = trim($variable, ' $&');

            $usedVariables[$variable] = $staticVariables[$variable];
        }

        return $usedVariables;
    }

    /**
     * Get the variables from the "use" clause.
     *
     * @return array
     */
    protected function getUseClauseVariables(): array
    {
        $begin = strpos($code = $this->getCode(), '(', $this->getUseIndex()) + 1;

        return explode(',', substr($code, $begin, strpos($code, ')', $begin) - $begin));
    }

    /**
     * Get the index location of the "use" clause.
     *
     * @return int
     */
    protected function getUseIndex(): int
    {
        return stripos(strtok($this->getCode(), PHP_EOL), ' use ');
    }

    /**
     * Serialize the Closure instance.
     *
     * @return array
     */
    public function __serialize(): array
    {
        return [
            'code' => $this->getCode(), 'variables' => $this->getVariables(),
        ];
    }

    /**
     * Unserialize the Closure instance.
     *
     * @param  array $payload
     * @return void
     */
    public function __unserialize(array $payload): void
    {
        // We will extract the variables into the current scope so that as the Closure
        // is built it will inherit the scope it had before it was serialized which
        // will emulate the Closures existing in that scope instead of right now.
        extract($payload['variables']);

        eval('$this->closure = '.$payload['code'].';');

        $this->reflection = new \ReflectionFunction($this->closure);
    }

    /**
     * Serialize the Closure instance. (PHP <=7.3)
     *
     * @return string|null
     */
    public function serialize(): ?string
    {
        return serialize([
            'code' => $this->getCode(), 'variables' => $this->getVariables()
        ]);
    }

    /**
     * Unserialize the Closure instance. (PHP >=7.4)
     *
     * @param  string $payload
     * @return void
     */
    public function unserialize($payload): void
    {
        $payload = unserialize($payload);

        // We will extract the variables into the current scope so that as the Closure
        // is built it will inherit the scope it had before it was serialized which
        // will emulate the Closures existing in that scope instead of right now.
        extract($payload['variables']);

        eval('$this->closure = '.$payload['code'].';');

        $this->reflection = new \ReflectionFunction($this->closure);
    }

    /**
     * Invoke the contained Closure.
     */
    public function __invoke()
    {
        return call_user_func_array($this->closure, func_get_args());
    }
}
