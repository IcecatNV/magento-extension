<?php

namespace Icecat\DataFeed\Observer\Adminhtml;

class LayoutHandler implements \Magento\Framework\Event\ObserverInterface
{
    public function __construct(\Magento\Framework\App\Request\Http\Proxy $request)
    {
        $this->request = $request;
    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $params = $this->request->getParams();

        if (! empty($params['section'])) {
            $moduleName = $this->getModuleName();
            if ($params['section'] == "datafeed") { 
                $layout = $observer->getData('layout');
                $layout->getUpdate()->addHandle('adminhtml_system_config_edit_section_icecat_config_handler');
            }
        }
    }

    private function getModuleName()
    {
        $class = get_class($this);
        $moduleName = strtolower(
            str_replace('\\', '_', substr($class, 0, strpos($class, '\\Observer')))
        );
        return (string) $moduleName;
    }
}