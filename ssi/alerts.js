/*
	import('/alerts.js').then(alerts => {
		this.alert = alerts.default.get('sthappend|.arrakis_alert'); // rel to get or selector to create
		this.alert.show('¿Qué tal se ve esta bonita cajita?', 'Prueba');
	});
	<div class="arrakis_alert" rel="sthappend">
		<div class="dialog">
			<span class="close"><svg viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg></span>
			<p class="title"></p>
			<p class="text"></p>
			<p class="actions"><button class="accept"><?=ln('') ?></button><button class="cancel"><?=ln('') ?></button></p>
		</div>
	</div>
 */

class Alert {

	constructor(elm) {
		this.elm_wrap = elm;
		this.elm_close = elm.querySelector('.close');
		this.elm_title = elm.querySelector('.title');
		this.elm_text  = elm.querySelector('.text');
		this.elm_actionst = elm.querySelector('.actionst');
		this.elm_accept = elm.querySelector('.accept');
		this.elm_cancel = elm.querySelector('.cancel');

		this.elm_wrap.addEventListener  ('click', this.close.bind(this));
		this.elm_close.addEventListener ('click', this.close.bind(this));
	}


	show(msg, tit = '') {
		if(this.elm_title && tit && tit.length) {
			this.elm_title.innerText = tit;
		}
		this.elm_text.innerHTML = msg;
		this.elm_wrap.classList.add('shown');
	}

	close() {
		this.elm_wrap.classList.remove('shown');
	}

}

var alerts = {};
var elms_alert = document.querySelectorAll('.arrakis_alert');
elms_alert.forEach(elm => {
	var key = elm.getAttribute('rel');
	alerts[key] = new Alert(elm)
});

console.log(alerts);

var oAlerts = {
	get: function (key) {
		if(alerts[key]) {
			return alerts[key];
		} else {
			var elm = document.querySelector('[rel="'+key+'"]');
			if(elm) alerts[key] = new Alert(elm);
		}
		return null;
	}
};

export default oAlerts;
