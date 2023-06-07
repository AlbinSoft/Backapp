
const urlbase = document.body.dataset.urlbase;
const uribase = (new URL(urlbase)).pathname;

const URLs = {
	logo () {
		return uribase+`images/logo-backapp.png`;
	},
	places () {
		return uribase+`places/`;
	},
	backups () {
		return uribase+`backups/`;
	},
	ajax () {
		return uribase+`index.php`;
	},
};

export default URLs;
