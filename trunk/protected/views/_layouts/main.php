<?php if(MLayout::isStrictDoctype()): ?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<?php else: ?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<?php endif; ?>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<meta name="language" content="en" /> 
<meta name="robots" content="all" />
<meta name="description" content="<?php echo Yii::app()->controller->getMetaDescription(); ?>" />
<meta name="keywords" content="<?php echo Yii::app()->controller->getMetaKeywords(); ?>" />
<link rel="shortcut icon" href="<?php echo Yii::app()->request->baseUrl; ?>/images/favicon.ico" type="image/x-icon" />
<?php _init::jsCss(); ?>
<?php /*MLayout::setWrapInGridCssClass(false);
      if(!MLayout::getWrapInGridCssClass()):
      Yii::app()->getClientScript()->registerCss('enlargeSidebar',".container_16 .grid_4 {width: 240px;}");
      endif;*/ ?>
<title><?php echo $this->pageTitle; ?></title>
</head>

<body class="<?php echo MLayout::getBodytagCssClass(); ?>">
<div class="w3-wrapper">

<div class="w3-header">
<div class="<?php echo MLayout::getContainerCssClass(); ?>">
<div class="<?php echo MLayout::getGridCssClass(); ?>">
<div class="w3-logo"><h1><?php echo CHtml::link(CHtml::encode(Yii::app()->params['title']),Yii::app()->homeUrl); ?></h1></div>
</div>
</div><!-- <?php echo MLayout::getContainerCssClass(); ?> -->
<div class="clear">&nbsp;</div>
<?php $this->widget('application.components.WMainMenu',array(
    'items'=>array(
        array('label'=>'Home', 'url'=>array('/site/index')),
        array('label'=>'Contact', 'url'=>array('/site/contact')),
        array('label'=>'Login', 'url'=>array('/site/login'), 'visible'=>Yii::app()->user->isGuest),
        array('label'=>'Logout', 'url'=>array('/site/logout'), 'visible'=>!Yii::app()->user->isGuest),
    ),
)); ?>
</div><!-- w3-header -->


<div class="clear"><a name="top">&nbsp;</a></div>

<div class="w3-top">
<div class="<?php echo MLayout::getContainerCssClass(); ?>">
<div class="<?php echo MLayout::getGridCssClass(); ?>">
<?php $this->widget('application.components.WUserFlash',array('type'=>'topSummary')); ?>
</div><!-- <?php echo MLayout::getGridCssClass(); ?> -->
<div class="clear">&nbsp;</div>
</div><!-- <?php echo MLayout::getContainerCssClass(); ?> -->
</div><!-- w3-top -->


<div class="<?php echo MLayout::getContainerCssClass(); ?>">
<div class="<?php echo MLayout::getGridCssClass(); ?>">
<div class="w3-main-wrapper">
<?php if(MLayout::hasSidebar1()): ?>

<div class="<?php echo MLayout::getGridCssClassSidebar1(); ?>">
<div class="w3-sidebar1">
<?php $this->widget('application.components.WUserFlash',array('type'=>'sidebarSummary','in'=>'sidebar1')); ?>
<?php $this->widget('application.components.WUserFlash',array('type'=>'sidebar1Summary')); ?>
<?php if(!MLayout::countSidebar1Item()): ?>
&nbsp;
<?php endif; ?>
</div><!-- w3-sidebar1 -->
</div><!-- <?php echo MLayout::getGridCssClassSidebar1(); ?> -->

<?php endif; ?>

<?php if(MLayout::hasContent()): ?>
<div class="<?php echo MLayout::getGridCssClassContent(); ?>">
<div class="w3-content">

<?php $this->widget('application.components.WUserFlash',array('type'=>'contentSummary')); ?>
<div class="w3-content-item<?php echo MLayout::countContentItem()?'':' first'; ?>">

<?php echo $content; ?>

</div><!-- w3-content-item -->

</div><!-- w3-content -->
</div><!-- <?php echo MLayout::getGridCssClassContent(); ?> -->
<?php endif; ?>

<?php if(MLayout::hasSidebar2()): ?>

<div class="<?php echo MLayout::getGridCssClassSidebar2(); ?>">
<div class="w3-sidebar2">
<?php $this->widget('application.components.WUserFlash',array('type'=>'sidebarSummary','in'=>'sidebar2')); ?>
<?php $this->widget('application.components.WUserFlash',array('type'=>'sidebar2Summary')); ?>
<?php if(!MLayout::countSidebar2Item()): ?>
&nbsp;
<?php endif; ?>
</div><!-- w3-sidebar2 -->
</div><!-- <?php echo MLayout::getGridCssClassSidebar2(); ?> -->
<?php endif; ?>

<div class="clear">&nbsp;</div>

</div><!-- w3-body-wrapper -->
</div>
</div>


<div class="clear"><a name="bottom">&nbsp;</a></div>

<div class="w3-bottom">
<div class="<?php echo MLayout::getContainerCssClass(); ?>">
<div class="<?php echo MLayout::getGridCssClass(); ?>">
<?php /* for your own purposes and for using with jquery */ ?>
</div><!-- <?php echo MLayout::getGridCssClass(); ?> -->
<div class="clear">&nbsp;</div>
</div><!-- <?php echo MLayout::getContainerCssClass(); ?> -->
</div><!-- w3-bottom -->


<div class="<?php echo MLayout::getContainerCssClass(); ?>">
<div class="<?php echo MLayout::getGridCssClass(); ?>">
<div class="w3-footer-wrapper">
<div class="w3-footer">
Copyright &copy; 2009 by <?php echo Yii::app()->params['copyrightBy']; ?>. All Rights Reserved.<br/>
<?php echo Yii::powered(); ?>
</div><!-- w3-footer -->
</div><!-- w3-footer-wrapper -->
</div>
<div class="clear">&nbsp;</div>
</div>

</div><!-- w3-wrapper -->
</body>

</html>