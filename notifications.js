// TODO Prompt
/*
	var fback;

	import('/notifications.js').then(fback => {
		window.fback = fback.default;
		window.fback.success('Información almacenada');
		window.fback.error('Información no almacenada');
	});
*/

var oFback = {
	init: function () {
		this.elm_board = document.querySelector('.board');
		this.elm_wrap  = document.createElement('ul');
		this.elm_wrap.classList.add('alert');
		this.elm_wrap.classList.add('close');
		this.elm_wrap.classList.add('hidden');
		this.elm_board.prepend(this.elm_wrap);
		this.elm_wrap.addEventListener('click', this.close.bind(this));
	},
	close: function() {
		this.elm_wrap.classList.add('hidden');
	},
	success: function(param) {
		this.elm_wrap.classList.add('success');
		this.elm_wrap.classList.remove('error');
		this.render(param);
	},
	error: function(param) {
		this.elm_wrap.classList.add('error');
		this.elm_wrap.classList.remove('success');
		this.render(param);
	},
	render: function(param) {
		var msgs = typeof(param)=='string' ? [param] : param;
		var elm_msg;
		this.elm_wrap.classList.remove('hidden');
		this.elm_wrap.innerHTML = '';
		msgs.map(msg => {
			elm_msg = document.createElement('li');
			elm_msg.innerText = msg;
			this.elm_wrap.appendChild(elm_msg);
		});
	},
};
oFback.init();

export default oFback;
