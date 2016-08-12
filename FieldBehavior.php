<?php

namespace frontend\components;

use Yii;
use yii\base\Behavior;
use yii\db\ActiveRecord;

class FieldBehavior extends Behavior
{

    public $attributes = [];
    private $_values = [];
    public $table = 'tv_text';
    /**
     * Events list
     * @return array
     */
    public function events()
    {
        return [
            ActiveRecord::EVENT_AFTER_FIND => 'afterFind',
            ActiveRecord::EVENT_AFTER_INSERT => 'afterInsert',
            ActiveRecord::EVENT_AFTER_UPDATE => 'afterUpdate',
            ActiveRecord::EVENT_BEFORE_DELETE => 'afterDelete',
        ];
    }

    /**
     * After find event.
     */
    public function afterFind()
    {
        $command = Yii::$app->db->createCommand('SELECT content FROM '.$this->table.' WHERE model=:model AND model_id=:model_id AND name=:name')                
                ->bindValue(':model', get_class($this->owner))
                ->bindValue(':model_id', $this->owner->getPrimaryKey());

        foreach ($this->attributes as $attributeName)
        {
            $attr = $command->bindValue(':name', $attributeName)->queryScalar();
            if ($attr !== null)
                $this->_values[$attributeName] = $attr;
        }
    }
    /**
     * After insert event.
     */
    public function afterInsert($event)
    {
        if (is_array($ownerPk = $this->owner->getPrimaryKey()))
        {
            throw new ErrorException("This behavior does not support composite primary keys");
        }

        $transaction = Yii::$app->db->beginTransaction();
        try
        {
            foreach ($this->attributes as $attributeName)
            {
                Yii::$app->db->createCommand()->insert($this->table, ['name' => $attributeName, 'content' => $this->owner->{$attributeName}, 'model' => get_class($this->owner), 'model_id' => $ownerPk])->execute();
            }
            $transaction->commit();
        } catch (Exception $ex)
        {
            $transaction->rollback();
            throw $ex;
        }
    }
    
    /**
     * After update event.
     */
    
    public function afterUpdate($event)
    {        
        if (is_array($ownerPk = $this->owner->getPrimaryKey()))
        {
            throw new ErrorException("This behavior does not support composite primary keys");
        }

        $transaction = Yii::$app->db->beginTransaction();
        try
        {
            foreach ($this->attributes as $attributeName)
            {
                Yii::$app->db->createCommand()->update($this->table, ['content' => $this->owner->{$attributeName}], ['name' => $attributeName, 'model' => get_class($this->owner), 'model_id' => $ownerPk])->execute();
            }
            $transaction->commit();
        } catch (Exception $ex)
        {
            $transaction->rollback();
            throw $ex;
        }
    }
    
    /**
     * After delete event.
     */
    
    public function afterDelete($event)
    {
        if (is_array($ownerPk = $this->owner->getPrimaryKey()))
        {
            throw new ErrorException("This behavior does not support composite primary keys");
        }

        $transaction = Yii::$app->db->beginTransaction();
        try
        {
            foreach ($this->attributes as $attributeName)
            {
                Yii::$app->db->createCommand()->delete($this->table, ['model' => get_class($this->owner), 'model_id' => $ownerPk])->execute();
            }
            $transaction->commit();
        } catch (Exception $ex)
        {
            $transaction->rollback();
            throw $ex;
        }
    }
    
    /**
     * Returns the value of an object property.
     * Get it from our local temporary variable if we have it,
     * 
     *
     * @param string $name the property name
     * @return mixed the property value
     * @see __set()
     */
    public function __get($name)
    {

        if (isset($this->_values[$name]))
        {
            return $this->_values[$name];
        } else
            return null;
    }

    /**
     * Sets the value of a component property. The data is passed
     *
     * @param string $name the property name or the event name
     * @param mixed $value the property value
     * @see __get()
     */
    public function __set($name, $value)
    {
        $this->_values[$name] = $value;
    }

    public function canGetProperty($name, $checkVars = true)
    {
        return in_array($name, $this->attributes) ?
                true : parent::canGetProperty($name, $checkVars);
    }

    public function canSetProperty($name, $checkVars = true, $checkBehaviors = true)
    {
        return in_array($name, $this->attributes) ?
                true : parent::canSetProperty($name, $checkVars, $checkBehaviors);
    }

}
