<?php


namespace Kinintel\Objects\Alert;


use Kinikit\Core\Binding\ObjectBinder;
use Kinikit\Core\DependencyInjection\Container;
use Kinikit\Persistence\ORM\ActiveRecord;
use Kinintel\Objects\Dataset\Tabular\TabularDataset;
use Kinintel\Services\Alert\MatchRule\AlertMatchRule;
use Kinintel\ValueObjects\Transformation\Filter\FilterTransformation;

/**
 * Class Alert
 * @package Kinintel\Objects\Alert
 *
 * @table ki_alert
 * @generate
 */
class Alert extends ActiveRecord {

    /**
     * @var integer
     */
    private $id;


    /**
     * @var integer
     */
    private $alertGroupId;


    /**
     * @var string
     * @required
     */
    private $title;

    /**
     * @var FilterTransformation
     * @json
     */
    private $filterTransformation;


    /**
     * @var string
     */
    private $matchRuleType;

    /**
     * @var mixed
     * @json
     */
    private $matchRuleConfiguration;


    /**
     * @var string
     * @sqlType LONGTEXT
     * @required
     */
    private $notificationTemplate;


    /**
     * @var string
     * @sqlType LONGTEXT
     * @required
     */
    private $summaryTemplate;


    /**
     * @var string
     */
    private $notificationCta;


    /**
     * Whether or not this alert is enabled
     *
     * @var bool
     */
    private $enabled = true;


    /**
     * Alert constructor.
     *
     * @param string $title
     * @param string $matchRuleType
     * @param mixed $matchRuleConfiguration
     * @param FilterTransformation $filterTransformation
     * @param string $notificationTemplate
     * @param string $notificationCta
     * @param string $summaryTemplate
     * @param integer $alertGroupId
     */
    public function __construct($title = null, $matchRuleType = "rowcount", $matchRuleConfiguration = null, $filterTransformation = null, $notificationTemplate = null, $notificationCta = null,
                                $summaryTemplate = null,
                                $alertGroupId = null) {
        $this->title = $title;
        $this->matchRuleType = $matchRuleType;
        $this->matchRuleConfiguration = $matchRuleConfiguration;
        $this->filterTransformation = $filterTransformation;
        $this->notificationTemplate = $notificationTemplate;
        $this->summaryTemplate = $summaryTemplate;
        $this->alertGroupId = $alertGroupId;
        $this->notificationCta = $notificationCta;
    }


    /**
     * @return int
     */
    public function getId() {
        return $this->id;
    }

    /**
     * @param int $id
     */
    public function setId($id) {
        $this->id = $id;
    }

    /**
     * @return int
     */
    public function getAlertGroupId() {
        return $this->alertGroupId;
    }

    /**
     * @param int $alertGroupId
     */
    public function setAlertGroupId($alertGroupId) {
        $this->alertGroupId = $alertGroupId;
    }

    /**
     * @return string
     */
    public function getTitle() {
        return $this->title;
    }

    /**
     * @param string $title
     */
    public function setTitle($title) {
        $this->title = $title;
    }

    /**
     * @return FilterTransformation
     */
    public function getFilterTransformation() {
        return $this->filterTransformation;
    }

    /**
     * @param FilterTransformation $filterTransformation
     */
    public function setFilterTransformation($filterTransformation) {
        $this->filterTransformation = $filterTransformation;
    }

    /**
     * @return string
     */
    public function getMatchRuleType() {
        return $this->matchRuleType;
    }

    /**
     * @param string $matchRuleType
     */
    public function setMatchRuleType($matchRuleType) {
        $this->matchRuleType = $matchRuleType;
    }

    /**
     * @return mixed
     */
    public function getMatchRuleConfiguration() {
        return $this->matchRuleConfiguration;
    }

    /**
     * @param mixed $matchRuleConfiguration
     */
    public function setMatchRuleConfiguration($matchRuleConfiguration) {
        $this->matchRuleConfiguration = $matchRuleConfiguration;
    }

    /**
     * @return string
     */
    public function getNotificationTemplate() {
        return $this->notificationTemplate;
    }

    /**
     * @param string $notificationTemplate
     */
    public function setNotificationTemplate($notificationTemplate) {
        $this->notificationTemplate = $notificationTemplate;
    }

    /**
     * @return string
     */
    public function getNotificationCta() {
        return $this->notificationCta;
    }

    /**
     * @param string $notificationCta
     */
    public function setNotificationCta($notificationCta) {
        $this->notificationCta = $notificationCta;
    }


    /**
     * @return string
     */
    public function getSummaryTemplate() {
        return $this->summaryTemplate;
    }

    /**
     * @param string $summaryTemplate
     */
    public function setSummaryTemplate($summaryTemplate) {
        $this->summaryTemplate = $summaryTemplate;
    }

    /**
     * @return bool
     */
    public function isEnabled() {
        return $this->enabled;
    }

    /**
     * @param bool $enabled
     */
    public function setEnabled($enabled) {
        $this->enabled = $enabled;
    }


    /**
     * Evaluate the match rule for a passed dataset and return boolean
     *
     * @param TabularDataset $dataSet
     *
     * @return boolean
     */
    public function evaluateMatchRule($dataSet) {

        /**
         * @var AlertMatchRule $matchRule
         */
        $matchRule = Container::instance()->getInterfaceImplementation(AlertMatchRule::class, $this->matchRuleType);

        // Grab the configuration class
        $configClass = $matchRule->getConfigClass();

        /**
         * @var ObjectBinder $objectBinder
         */
        $objectBinder = Container::instance()->get(ObjectBinder::class);
        $configuration = $objectBinder->bindFromArray($this->matchRuleConfiguration, $configClass);

        // Return boolean
        return $matchRule->matchesRule($dataSet, $configuration);

    }


}
