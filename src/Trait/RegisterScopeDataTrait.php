<?php

declare(strict_types=1);

namespace UtilityKit\Trait;

trait RegisterScopeDataTrait
{
    /**
     * @var string
     */
    protected string $defaultScope = 'default';

    /**
     * @var string
     */
    protected string $currentScope = 'default';

    /**
     * @var array
     */
    protected array $scopeItems = [];

    /**
     * @param mixed $data
     * @param string|null $scope
     * @return self
     */
    public function setScopeData(mixed $data, string $scope = null): self
    {
        $scope = $this->getScopeName($scope);
        $this->scopeItems[$scope][] = $data;

        return $this;
    }

    /**
     * @param string|null $scope
     * @return mixed
     */
    public function getScopeData(string $scope = null): mixed
    {
        $scope = $this->getScopeName($scope);

        return $this->scopeItems[$scope] ?? [];
    }

    /**
     * @param string|null $scope
     * @return self
     */
    public function deleteScopeData(?string $scope = null): self
    {
        $scope = $this->getScopeName($scope);
        unset($this->scopeItems[$scope]);

        return $this;
    }

    /**
     * @return self
     */
    public function defaultScope(): self
    {
        $this->currentScope = $this->defaultScope;

        return $this;
    }

    /**
     * @param string $scope
     * @param boolean $overwrite
     * @return self
     */
    public function withScope(string $scope, bool $overwrite = false)
    {
        if ($overwrite) {
            $this->deleteScopeData($scope);
        }

        $this->currentScope = $scope;

        return $this;
    }

    /**
     * @param string|null $scope
     * @return string
     */
    protected function getScopeName(string $scope = null): string
    {
        return $scope ?? $this->currentScope ?? $this->defaultScope;
    }
}
