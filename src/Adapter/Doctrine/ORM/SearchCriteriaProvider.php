<?php

/*
 * Symfony DataTables Bundle
 * (c) Omines Internetbureau B.V. - https://omines.nl/
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Omines\DataTablesBundle\Adapter\Doctrine\ORM;

use Doctrine\ORM\Query\Expr\Comparison;
use Doctrine\ORM\QueryBuilder;
use Omines\DataTablesBundle\Column\AbstractColumn;
use Omines\DataTablesBundle\DataTableState;

/**
 * SearchCriteriaProvider.
 *
 * @author Niels Keurentjes <niels.keurentjes@omines.com>
 */
class SearchCriteriaProvider implements QueryBuilderProcessorInterface
{
    private static $paramIndex = 0;

    public function process(QueryBuilder $queryBuilder, DataTableState $state): void
    {
        $this->processSearchColumns($queryBuilder, $state);
        $this->processGlobalSearch($queryBuilder, $state);
    }

    private function processSearchColumns(QueryBuilder $queryBuilder, DataTableState $state): void
    {
        foreach ($state->getSearchColumns() as $searchInfo) {
            /** @var AbstractColumn $column */
            $column = $searchInfo['column'];
            $search = $searchInfo['search'];

            if ('' !== trim($search)) {
                if (null !== ($filter = $column->getFilter())) {
                    if (!$filter->isValidValue($search)) {
                        continue;
                    }
                }
                $expr = $queryBuilder->expr();

                $paramName = $this->getParamName();
                $queryBuilder->andWhere(new Comparison(
                    $column->getLeftExpr(),
                    $column->getOperator(),
                    $column->getRightExpr($paramName)
                ))
                ->setParameter($paramName, $column->getSearchValue($search));
            }
        }
    }

    private function getParamName(): string
    {
        ++static::$paramIndex;

        return 'dtSearchParam_'.static::$paramIndex;
    }

    private function processGlobalSearch(QueryBuilder $queryBuilder, DataTableState $state): void
    {
        if (!empty($globalSearch = $state->getGlobalSearch())) {
            $expr = $queryBuilder->expr();
            $comparisons = $expr->orX();
            foreach ($state->getDataTable()->getColumns() as $column) {
                if ($column->isGlobalSearchable() && !empty($column->getField()) && $column->isValidForSearch($globalSearch)) {
                    $paramName = $this->getParamName();
                    $comparisons->add(new Comparison(
                        $column->getLeftExpr(),
                        $column->getOperator(),
                        $column->getRightExpr($paramName)
                    ));
                    $queryBuilder->setParameter($paramName, $column->getSearchValue($globalSearch));
                }
            }
            $queryBuilder->andWhere($comparisons);
        }
    }
}
