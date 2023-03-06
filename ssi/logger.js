
odl(function () {
	var shown = false;
	var elm_logger = document.querySelector('.logger');
	window.addEventListener('keyup', (evt) => {
		if((evt.keyCode==76 || evt.keyCode==108) && evt.shiftKey) {
			elm_logger.classList.toggle('shown'); shown = !shown;
		}
		if(evt.keyCode==27) {
			elm_logger.classList.remove('shown'); shown = false;
		}
		if(shown && [49,50,51,52,53,54,55,56,57].includes(evt.keyCode)) {
			show_tab(evt.keyCode-49);
		}
	});

	var elms_tab  = document.querySelectorAll('.logger_tabs span');
	var elms_cont = document.querySelectorAll('.logger_cont');
	var show_tab  = function (itr, evt) {
		evt && evt.preventDefault();
		if(elms_tab[itr] && elms_cont[itr]) {
			elms_tab.forEach(elm => elm.classList.remove('sel'));
			elms_cont.forEach(elm => elm.classList.remove('shown'));
			elms_tab[itr].classList.add('sel');
			elms_cont[itr].classList.add('shown');
		}
	}
	elms_tab.forEach((elm, itr) => elm.addEventListener('click', show_tab.bind(null, itr)));
	show_tab(0);
});
