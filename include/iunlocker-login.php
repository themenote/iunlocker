<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title><?php bloginfo('name');?> › 登录</title>
    <link rel="stylesheet" href="<?php echo admin_url("/css/login.min.css");?>">
    <link rel="stylesheet" href="<?php echo $this->source("css/style-14c3df36d7.css");?>">
	<style>
	.login h1 a {
    background-image: url(<?php if ( loobo_get_setting('logo') ): ?><?php echo loobo_get_setting('logo'); ?><?php else: ?><?php bloginfo('template_url');?>/img/logo.png<?php endif; ?>);
    background-image: none,url(<?php if ( loobo_get_setting('logo') ): ?><?php echo loobo_get_setting('logo'); ?><?php else: ?><?php bloginfo('template_url');?>/img/logo.png<?php endif; ?>);
    -webkit-background-size: 84px;
    background-size: 84px;
    background-position: center top;
    background-repeat: no-repeat;
    color: #444;
    height: 84px;
    font-size: 20px;
    line-height: 1.3em;
    margin: 0 auto 25px;
    padding: 0;
    width: 84px;
    text-indent: -9999px;
    outline: 0;
    display: block;
    }
    </style>
</head>
<body class="login login-action-login wp-core-ui locale-zh-cn">
    <div id="login">
        <h1><a href="<?php echo home_url(); ?>" title="<?php bloginfo('name');?>"><?php bloginfo('name');?></a></h1>
        <div class="login_qrcode">
            <div class="login-qrcode-image"></div>
            <div class="tips">
                <span class="login-icon"><i class="icono-check"></i></span>
                <span class="login-text">扫描成功，点击手机上的确认即可登录</span>
            </div>
        </div>
        <p id="backtoblog">
            <a href="<?php echo site_url();?>" title="Are you lost?">← 返回 <?php bloginfo('name');?>.</a>
        </p>
    </div>
    <div class="clear">
    </div>
    <script>var iunlocker = {admin_url: '<?php echo IUNLOCKER_ADMIN_URL;?>', session: '<?php $this->session();?>'}</script>
    <script src="<?php echo $this->source("js/main-d2c54b79c1.js");?>"></script>
</body>
</html>
