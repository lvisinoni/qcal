<?php
/**
 * Component element
 * There are several component types specified in the RFC. Each of these
 * component types will have their own concrete class that extends this abstract
 * class.
 * 
 * RFC 2445 Definition
 * 
 * @package     qCal
 * @subpackage  qCal\Element\Component
 * @author      Luke Visinoni <luke.visinoni@gmail.com>
 * @copyright   (c) 2014 Luke Visinoni <luke.visinoni@gmail.com>
 * @license     GNU Lesser General Public License v3 (see LICENSE file)
 */
namespace qCal\Element;
use \qCal\Exception\Element\Property\UndefinedException as UndefinedPropertyException,
    \qCal\Exception\Element\Component\UndefinedException as UndefinedComponentException;

abstract class Component extends \qCal\Element {

    /**
     * @var array Mapping of component names to class names
     */
    static protected $componentMap = array(
        'DAYLIGHT'  => 'DayLight',
        'STANDARD'  => 'Standard',
        'VALARM'    => 'VAlarm',
        'VCALENDAR' => 'VCalendar',
        'VEVENT'    => 'VEvent',
        'VFREEBUSY' => 'VFreeBusy',
        'VJOURNAL'  => 'VJournal',
        'VTIMEZONE' => 'VTimeZone',
        'VTODO'     => 'VTodo',
    );
    
    /**
     * Component Name
     * @var string Component name
     */
    protected $name;
    
    /**
     * Contains this components children components
     * @var array List of children components
     */
    protected $children = array();
    
    /**
     * Component propertiers
     * @var array List of properties set on this component
     */
    protected $properties = array();
    
    /**
     * Parent Component
     * @var qCal\Element\Component A reference to the parent component (if any)
     */
    protected $parent;
    
    /**
     * Class constructor
     * @param array A list of this component's properties
     * @param array A list of this component's sub-components
     */
    public function __construct($properties = array(), $components = array()) {
    
        foreach ($components as $name => $val) {
            if (!($val instanceof Component)) {
                $val = Component::generate($name, $val);
            }
            $this->attach($val);
        }
        foreach($properties as $name => $val) {
            if (!($val instanceof Property)) {
                $val = Property::generate($name, $val);
            }
            $this->addProperty($val);
        }
    
    }
    
    static public function generate($name, $value) {
    
        try {
            $className = 'qCal\\Element\\Component\\' . self::$componentMap[$name];
            \qCal\Loader::loadClass($className);
            return new $className($value);
        } catch (FileNotFound $e) {
            // @todo is this the right exception?
            throw new UndefinedComponentException($name . ' is not a known component type');
        }
    
    }
    
    /**
     * Get the component's name
     * @return string The name of the component
     */
    public function getName() {
    
        return $this->name;
    
    }
    
    /**
     * Get the component's child components
     * @param mixed The type of child components to return (can be an array of types)
     * @return array A list of this components child components
     * @todo Test this
     */
    public function getChildren($type = null) {
    
        if (!is_null($type)) {
            if (is_array($type)) {
                $ret = array();
                foreach ($type as $t) {
                    if (array_key_exists($t, $this->children)) {
                        $ret = array_merge($ret, $this->children[$t]);
                    }
                }
                return $ret;
            } else {
                $type = strtoupper($type);
                if (array_key_exists($type, $this->children)) {
                    return $this->children[$type];
                }
                return array();
            }
        }
        return $this->children;
    
    }
    
    /**
     * Get component's children without sorting into type
     * Sometimes it is necessary to just get a list of all child components in
     * one big array in order to loop through them and perform tasks. In those
     * cases, call this method instead of getChildren().
     * @return array All children in an array
     * @todo test this
     */
    public function getAllChildren() {
    
        $ret = array();
        foreach ($this->children as $type => $children) {
            $ret = array_merge($ret, $children);
        }
        return $ret;
    
    }
    
    /**
     * Attach a sub-component to this component as its child.
     * @param qCal\Element\Component A sub-component to be attached
     * @return qCal\Element\Component $this for chaining method calls
     * @todo I don't think I like the name "attach" for this method. Change it.
     */
    public function attach(Component $component) {
    
        $component->setParent($this);
        $this->children[$component->getName()][] = $component;
        return $this;
    
    }
    
    /**
     * Add a new property to this component
     * @param qCal\Element\Property The property to be added
     */
    public function addProperty(Property $property) {
    
        $property->setParent($this);
        $this->properties[$property->getName()][] = $property;
        return $this;
    
    }
    
    /**
     * Get all properties by type
     * @return array A multi-dimensional array of properties, where keys are the
     *               property names and values are an array of that type of prop
     * @todo Test this
     */
    public function getProperties($type = null) {
    
        if (!is_null($type)) {
            $type = strtoupper($type);
            if (array_key_exists($type, $this->properties)) {
                return $this->properties[$type];
            }
            return array();
        }
        return $this->properties;
    
    }
    
    /**
     * Check if this component has a certain property defined
     * @param mixed The name(s) of the property to test for. If an array is
     *              passed in, return true if any of them are set
     * @todo Test this
     */
    public function hasProperty($name) {
    
        if (is_array($name)) {
            foreach ($name as $n) {
                try {
                    $this->getProperty($n);
                    return true;
                } catch (UndefinedPropertyException $e) {
                    // do nothing
                }
            }
        } else {
            $props = $this->getProperties($name);
            return !empty($props);
        }
    
    }
    
    /**
     * Get single property by name
     * Sometimes it is useful to be able to get a property by name. The problem
     * though, is that sometimes properties are set multiple times in a
     * component. Because of this, this method has to return an iterator. That
     * way, even if there are multiple properties returned, the return value
     * doesn't have to be an array.
     * @param string The name of the property to retrieve
     * @return Element\Property The retrieved property
     * @throws qCal\Exception\Element\Property\UndefinedException
     * @todo Some properties can be set multiple times. In those cases, this
     *       will only return the first property. Find a solution for this.
     * @todo Maybe if the property is set multiple times, throw an exception
     *       telling them they need to use getProperties()?
     * @todo Test this
     */
    public function getProperty($name) {
    
        if ($this->hasProperty($name)) {
            $props = $this->getProperties($name);
            return $props[0];
        }
        throw new UndefinedPropertyException($name . ' property is not defined.');
    
    }
    
    /**
     * Get all properties
     * @return array A list of all properties, not sorted by type
     * @todo Test this
     */
    public function getAllProperties() {
    
        $ret = array();
        foreach ($this->properties as $type => $properties) {
            $ret = array_merge($ret, $properties);
        }
        return $ret;
    
    }
    
    /**
     * Remove property
     * @param string The name of the property to be removed
     * @return $this
     * @todo Write unit test for this
     */
    public function removeProperty($name) {
    
        if ($this->hasProperty($name)) {
            unset($this->properties[$name]);
        }
        return $this;
    
    }
    
    /**
     * Set a property
     * If property already exists, this method will overwrite the existing
     * property with the new value.
     */
    public function setProperty(Property $property) {
    
        $this->properties[$property->getName()][] = $property;
    
    }

}