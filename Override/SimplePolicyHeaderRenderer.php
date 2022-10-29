<?php
/**
 * PixieMedia_Csp extension
 *                     NOTICE OF LICENSE
 * 
 *                     This source file is subject to the MIT License
 *                     that is bundled with this package in the file LICENSE.txt.
 *                     It is also available through the world-wide-web at this URL:
 *                     http://opensource.org/licenses/mit-license.php
 * 
 *                     @category  PixieMedia
 *                     @package   PixieMedia_Csp
 *                     @copyright Copyright (c) 2022
 *                     @license   http://opensource.org/licenses/mit-license.php MIT License
 */

namespace PixieMedia\Csp\Override;

use Magento\Csp\Api\Data\ModeConfiguredInterface;
use Magento\Csp\Api\Data\PolicyInterface;
use Magento\Csp\Api\ModeConfigManagerInterface;
use Magento\Csp\Api\PolicyRendererInterface;
use Magento\Csp\Model\Policy\SimplePolicyInterface;
use Magento\Framework\App\Response\HttpInterface as HttpResponse;


class SimplePolicyHeaderRenderer extends \Magento\Csp\Model\Policy\Renderer\SimplePolicyHeaderRenderer 
{
	
	/**
     * @var ModeConfigManagerInterface
     */
    private $modeConfig;

    /**
     * @param ModeConfigManagerInterface $modeConfig
     */
    public function __construct(
		ModeConfigManagerInterface $modeConfig,
		\Magento\Framework\App\Request\Http $request)
    {
        $this->modeConfig = $modeConfig;
		$this->request = $request;
    }


	
	/**
     * @inheritDoc
     */
    public function render(PolicyInterface $policy, HttpResponse $response): void
    {
        /** @var SimplePolicyInterface $policy */
        $config = $this->modeConfig->getConfigured();
		
		if ($config->isReportOnly()) {
            $header = 'Content-Security-Policy-Report-Only';
        } else {
            $header = 'Content-Security-Policy';
        }
		
		// **************************************************************//
		// PIXIE MEDIA - TEMP SOLUTION TO 3DS2 WITH CSP....
		// Check for policy frame-src, frame-ancestors & is checkout,    // 
		// then allow all urls in iframes to support 3DSv2 urls          //
		// **************************************************************//
		
		$polId      = $policy->getId();
        $route      = $this->request->getRouteName();
		$override   = (($polId == 'frame-src' || $polId == 'frame-ancestors' || $polId == 'form-action') && $route == 'checkout')?true:false;
		if($override) {
			$value = $policy->getId() .' * data: blob: ;';
		} else {
			$value = $policy->getId() .' ' .$policy->getValue() .';';
		}
        
        if ($config->getReportUri() && !$response->getHeader('Report-To')) {
            $reportToData = [
                'group' => 'report-endpoint',
                'max_age' => 10886400,
                'endpoints' => [
                    ['url' => $config->getReportUri()]
                ]
            ];
            $value .= ' report-uri ' .$config->getReportUri() .';';
            $value .= ' report-to '. $reportToData['group'] .';';
            $response->setHeader('Report-To', json_encode($reportToData), true);
        }
		
        if (($existing = $response->getHeader($header)) && !$override) {
            $value = $value .' ' .$existing->getFieldValue();
        }
		
        $response->setHeader($header, $value, true);
    }

	
	
		
	
}