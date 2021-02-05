<?php


namespace Pimcore\Bundle\DataHubBatchImportBundle\Mapping\Operator\Factory;


use Pimcore\Bundle\DataHubBatchImportBundle\Exception\InvalidConfigurationException;
use Pimcore\Bundle\DataHubBatchImportBundle\Mapping\Operator\AbstractOperator;
use Pimcore\Bundle\DataHubBatchImportBundle\Mapping\Type\TransformationDataTypeService;

class Boolean extends AbstractOperator
{

    public function process($inputData, bool $dryRun = false)
    {

        if(is_array($inputData)) {
            $inputData = reset($inputData);
        }

        if($inputData === 'false') {
            return false;
        }

        return boolval($inputData);
    }

    /**
     * @param string $inputType
     * @param int|null $index
     * @return string
     * @throws InvalidConfigurationException
     */
    public function evaluateReturnType(string $inputType, int $index = null): string {

        if(!in_array($inputType, [TransformationDataTypeService::DEFAULT_TYPE, TransformationDataTypeService::BOOLEAN])) {
            throw new InvalidConfigurationException(sprintf("Unsupported input type '%s' for boolean operator at transformation position %s", $inputType, $index));
        }

        return TransformationDataTypeService::BOOLEAN;

    }

}
