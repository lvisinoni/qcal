<?php
/**
 * Percent Complete Property Conformance
 *
 * RFC 5545 Definition
 *
 * Conformance: The property can be specified in a "VEVENT" or "VTODO"
 * calendar component.
 *
 * @package     qCal
 * @subpackage  qCal\Conformance
 * @author      Luke Visinoni <luke.visinoni@gmail.com>
 * @copyright   (c) 2014 Luke Visinoni <luke.visinoni@gmail.com>
 * @license     GNU Lesser General Public License v3 (see LICENSE file)
 */
namespace qCal\Conformance\Property;
use \qCal\Element,
    \qCal\Exception\Conformance\Exception as ConformanceException;

class Priority extends \qCal\Conformance\Property {

    /**
     * @var array A list of components the property is allowed to be defined on.
     */
    protected $allowedComponents = array('VEVENT','VTODO');
    
    /**
     * Check that this property is conformant
     * @param qCal\Element\Property
     */
    public function conform(Element\Property\Priority $property) {
    
        return parent::conform($property);
    
    }

}