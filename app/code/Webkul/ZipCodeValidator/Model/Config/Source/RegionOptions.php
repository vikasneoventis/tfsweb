<?php
/**
 * Webkul Software.
 *
 * @category  Webkul
 * @package   Webkul_ZipCodeValidator
 * @author    Webkul
 * @copyright Copyright (c) 2010-2017 Webkul Software Private Limited (https://webkul.com)
 * @license   https://store.webkul.com/license.html
 */
namespace Webkul\ZipCodeValidator\Model\Config\Source;

use Magento\Eav\Model\Entity\Attribute\Source\AbstractSource;

class RegionOptions extends AbstractSource
{
    /**
     * Region Collection
     *
     * @var \Magento\Customer\Model\Session
     */
    private $regionCollection;

    /**
     * Construct
     *
     * @param \Webkul\ZipCodeValidator\Model\ResourceModel\Region\CollectionFactory $regionCollectionFactory
     */
    public function __construct(
        \Webkul\ZipCodeValidator\Model\ResourceModel\Region\CollectionFactory $regionCollectionFactory
    ) {
        $this->regionCollection = $regionCollectionFactory;
    }
    /**
     * Get all options
     *
     * @return array
     */
    public function getAllOptions()
    {
        $collections = $this->regionCollection->create()
            ->addFieldToFilter('status', 1);
        if ($this->_options === null) {
            foreach ($collections as $region) {
                $this->_options[] = [
                    'label' => __($region->getRegionName()),
                    'value' => $region->getId(),
                ];
            }
        }
        return $this->_options;
    }
}
