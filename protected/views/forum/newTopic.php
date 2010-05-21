<?php
MParams::setPageLabel(Yii::t('page','Post a new topic'));
MUserFlash::setTopError(_CHtml::errorSummary(array($forumPosts,$forumTopics)));
MUserFlash::setSidebarInfo(Yii::t('hint','Required: {authRoles}.',    array(2,'{authRoles}'=>implode(', ',array(Yii::t('t',User::MEMBER_T),Yii::t('t',User::MANAGER_T),Yii::t('t',User::ADMINISTRATOR_T))))
));?>
<?php echo $this->renderPartial('_common'); ?>
<div class="w3-main-form-box ui-widget-content ui-corner-all">
    <?php echo _CHtml::beginForm('','post',array('class'=>'w3-main-form'))."\n"; ?>
    <div class="w3-form-row w3-first">
        <div class="w3-form-row-label"><?php echo _CHtml::activeLabelEx($forumPosts,'title'); ?></div>
        <div class="w3-form-row-input">
            <?php echo _CHtml::activeTextField($forumPosts,'title',array('class'=>'w3-input-text ui-widget-content ui-corner-all','maxlength'=>255))."\n"; ?>
        </div>
        <div class="clear">&nbsp;</div>
    </div>
    <div class="w3-form-row">
        <div class="w3-form-row-label"><?php echo _CHtml::activeLabelEx($forumPosts,'shortContent'); ?></div>
        <div class="w3-form-row-input">
            <?php echo _CHtml::activeTextField($forumPosts,'shortContent',array('class'=>'w3-input-text ui-widget-content ui-corner-all'))."\n"; ?>
        </div>
        <div class="clear">&nbsp;</div>
    </div>
    <div class="w3-form-row">
        <div class="w3-form-row-label"><?php echo _CHtml::activeLabelEx($forumPosts,'content'); ?></div>
        <div class="w3-form-row-input">
            <?php echo _CHtml::activeTextArea($forumPosts,'content',array('class'=>'w3-input-text ui-widget-content ui-corner-all'))."\n"; ?>
        </div>
        <div class="clear">&nbsp;</div>
    </div>
    <div class="w3-form-row">
        <div class="w3-form-row-label">&nbsp;</div>
        <div class="w3-form-row-input">
            <div class="w3-form-row-text">
                <?php echo Yii::t('hint','{saveButton} or {cancelLink}',array(
                        '{saveButton}'=>_CHtml::submitButton(Yii::t('link','Post'),array('class'=>'w3-input-button ui-state-default ui-corner-all')),
                        '{cancelLink}'=>CHtml::link(Yii::t('link','Cancel[form]'),array($this->id.'/')),
                        ))."\n"; ?>
            </div>
        </div>
        <div class="clear">&nbsp;</div>
    </div>
    <?php echo _CHtml::endForm(); ?>
</div><!-- w3-main-form-box -->