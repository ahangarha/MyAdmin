/**
 * Simple Sidebar
 * Version : 1.2
 * Update  : 08 August 2015
 *
 * @author    : Ali Bakhtiar
 * @copyright : Copyright (c) alibakhtiar.com
 * @license   : MIT (http://opensource.org/licenses/MIT)
*/

$.fn.sidebar = function(options){

	var $stt = $.extend({
		mod: 1,
		controller: '.sidebar-toggle',
		main_div: null,
		rtl: false,
		open: null,
		open_size: null,
		close_size: null,
		set_height: true,
		top: 0,
		time: 500,
		easing: null,
		hover: false,

		before_open: false,
		after_open: false,
		before_close: false,
		after_close: false

	}, arguments[0] || {});

	var _sidebar = $(this);
	var controller = $($stt.controller);
	var $o_size = (null == $stt.open_size) ? $(this).width() : $stt.open_size;
	var $c_size = (null == $stt.close_size) ? 0 : $stt.close_size;
	
	/** Main Div */
	if(null == $stt.main_div)
	{
		var nxt = $(this).next('div').attr("id");
		if(!nxt){
			return false;
		}

		$stt.main_div = '#'+nxt;
	};

	/** CSS */
	if(true == $stt.rtl){
		$(this).css({right:0, left:'auto'});
	}else{
		$(this).css({right:'auto', left:0});
	};

	/** Height */
	_height();
	$(window).resize(function(){
		_height();
	});

	/** Check */
	var st; 
	if(null != $stt.open){
		st = (true == $stt.open || 'open' == $stt.open) ? 'open' : 'close';
	}else{
		st = get_st();
	};

	if('open' == st){
		_open(1);
		set_st('open');
	}else{
		_close(1);
		set_st('close');
	};


	/** Click */
	controller.on('click', function(e){
		st = get_st();
		if('open' == st){
			_close(0);
			set_st('close');
		}else{
			_open(0);
			set_st('open');
		}
    });


	/** Open */
	function _open(f)
	{
		set_st('open');

		/* Before Open */
		if(typeof $stt.before_open == 'function'){
			$stt.before_open.call(this);
		}

		if(1 == $stt.mod){
			open_1(f);
		}else{
			open_2(f);
		}

		/* After Open */
		if(typeof $stt.after_open == 'function'){
			$stt.after_open.call(this);
		}
	};


	/** Close */
	function _close(f)
	{
		set_st('close');

		/* Before Close */
		if(typeof $stt.before_close == 'function'){
			$stt.before_close.call(this);
		}

		if(1 == $stt.mod){
			close_1(f);
		}else{
			close_2(f);
		}

		/* After Close */
		if(typeof $stt.after_close == 'function'){
			$stt.after_close.call(this);
		}
	};

	
	/** Open 1 */
	function open_1(f)
	{
		var $t = (1==f) ? 0 : $stt.time;
		/*RTL*/
		if(true == $stt.rtl)
		{
			$($stt.main_div).animate({
				right: $o_size,
				left: 'auto',
				width: ($(this).width() - $o_size)
			}, $t, $stt.easing);

			return;
		}

		/*LTR*/
		$($stt.main_div).animate({
			left: $o_size,
			right: 'auto',
			width: ($(this).width() - $o_size)
		}, $t, $stt.easing);
	};


	/** Open 2 */
	function open_2(f)
	{
		var $t = (1==f) ? 0 : $stt.time;
		/*RTL*/
		if(true == $stt.rtl)
		{
			_sidebar.animate({
				right: 0
			}, $t, $stt.easing);
	
			$($stt.main_div).animate({
				'margin-right': $o_size
			}, $t, $stt.easing);
			return;
		}

		/*LTR*/
		_sidebar.animate({
			left: 0
		}, $t, $stt.easing);

		$($stt.main_div).animate({
			'margin-left': $o_size
		}, $t, $stt.easing);
	};


	/** Close 1 */
	function close_1(f)
	{
		var $t = (1==f) ? 0 : $stt.time;
		/*RTL*/
		if(true == $stt.rtl)
		{
			$($stt.main_div).animate({
				right: $c_size,
				width: ($(this).width() - $c_size)
			}, $t, $stt.easing);

			return;
		}

		/*LTR*/
		$($stt.main_div).animate({
			left: $c_size,
			width: ($(this).width() - $c_size)
		}, $t, $stt.easing);
	};


	/** Close 2 */
	function close_2(f)
	{
		var $t = (1==f) ? 0 : $stt.time;
		/*RTL*/
		if(true == $stt.rtl)
		{
			_sidebar.animate({
				right: '-'+$o_size
			}, $t, $stt.easing);

			$($stt.main_div).animate({
				'margin-right': 0
			}, $t, $stt.easing);
			
			return;
		}

		/*LTR*/
		_sidebar.animate({
			left: '-'+$o_size
		}, $t, $stt.easing);
	
		$($stt.main_div).animate({
			'margin-left': 0
		}, $t, $stt.easing);
	};


	/** Get Status */
	function get_st()
	{
		var gst = controller.data('open');
		if(!gst){
			return 'false';
		}
		if(true == gst || 'true' == gst || 'open' == gst){
			return 'open';
		}
		else{
			return 'close';
		}
	};


	/** Set Status */
	function set_st(st)
	{
		var sst = ('open'==st) ? true : false;
		controller.data('open', sst);
	};


	/** Height */
	function _height()
	{
		if(false == $stt.set_height){
			return;
		}

		if(typeof $stt.set_height == 'function'){
			$stt.set_height.call(this);
		}
		else{
			var wh = $(window).height();
			_sidebar.height(wh - $stt.top);
		}
	};


	/** Public */
	return {
		open : function(){
			_open();
		},

		close : function(){
			_close();
		}
	}
};