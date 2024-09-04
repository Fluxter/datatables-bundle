<?php

/*
 * Symfony DataTables Bundle
 * (c) Omines Internetbureau B.V. - https://omines.nl/
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Omines\DataTablesBundle\Column;

use Symfony\Component\OptionsResolver\OptionsResolver;

use function is_string;

/**
 * TextColumn.
 *
 * @author Niels Keurentjes <niels.keurentjes@omines.com>
 */
class TextColumn extends AbstractColumn
{
    public function normalize(mixed $value): string
    {
        $value = (string) $value;

        $normalizedValue = $this->isRaw() ? $value : htmlspecialchars($value, \ENT_QUOTES | \ENT_SUBSTITUTE);

        // if (!$this->options['case_sensitive']) {
        //     $normalizedValue = 'LOWER('.$normalizedValue.')';
        // }

        return $normalizedValue;
    }

    public function getLeftExpr(): mixed
    {
        $expr = parent::getLeftExpr();

        if (!$this->options['case_sensitive']) {
            $expr = 'LOWER('.$expr.')';
        }

        return $expr;
    }

    public function getSearchValue(string|int $value): string|int
    {
        if (!is_string($value)) {
            return $value;
        }

        return '%'.$value.'%';
    }

    public function getRightExpr(string $paramName): string
    {
        $expr = parent::getRightExpr($paramName);

        if (!$this->options['case_sensitive']) {
            $expr = 'LOWER('.$expr.')';
        }

        return $expr;
    }

    public function isRaw(): bool
    {
        return $this->options['raw'];
    }

    protected function configureOptions(OptionsResolver $resolver): static
    {
        parent::configureOptions($resolver);

        $resolver
            ->setDefault('operator', 'LIKE')
            ->setDefault('case_sensitive', false);

        $resolver
            ->setDefault('raw', false)
            ->setAllowedTypes('raw', 'bool')
        ;

        return $this;
    }
}
