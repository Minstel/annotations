<?php

declare(strict_types=1);

namespace Jasny\PhpdocParser\Tag\PhpDocumentor;

use Jasny\PhpdocParser\Tag\AbstractTag;
use Jasny\PhpdocParser\notation;
use function Jasny\array_only;

/**
 * Custom logic for PhpDocumentor 'var', 'param' and 'property' tag
 */
class VarTag extends AbstractTag
{
    /**
     * @var array
     */
    protected $additional;

    /**
     * @var callable|null
     */
    protected $fqsenConvertor;

    /**
     * Class constructor.
     *
     * @param string        $name            Tag name
     * @param callable|null $fqsenConvertor  Logic to convert class to FQCN
     * @param array         $additional      Additional properties
     */
    public function __construct(string $name, ?callable $fqsenConvertor = null, array $additional = [])
    {
        parent::__construct($name);

        $this->fqsenConvertor = $fqsenConvertor;
        $this->additional = $additional;
    }

    /**
     * Get additional properties that are always applied.
     *
     * @return array
     */
    public function getAdditionalProperties(): array
    {
        return $this->additional;
    }

    /**
     * Process a notation.
     *
     * @param array  $notations
     * @param string $value
     * @return array
     * @throws notation
     */
    public function process(array $notations, string $value): array
    {
        $regexp = '/^(?:(?<type>[^$\s]+)\s*)?(?:\$(?<name>\w+)\s*)?(?:"(?<id>[^"]+)")?/';
        preg_match($regexp, $value, $props); //regexp won't fail

        if (isset($props['type']) && $props['type'] === '') {
            unset($props['type']);
        }

        if (isset($props['type']) && isset($this->fqsenConvertor)) {
            $props['type'] = call_user_func($this->fqsenConvertor, $props['type']);
        }

        $props = array_only($props, ['type', 'name', 'id']);

        $notations[$this->name] = $props + $this->additional;

        return $notations;
    }
}
