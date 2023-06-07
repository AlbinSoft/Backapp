
export default {
	setJSON: (key, val) => {
		let json = JSON.stringify(val);
		let ok = localStorage.setItem(key, json);
//		console.log('setJSON', key, json, ok);
	},
	getJSON: (key, def) => {
		let json = localStorage.getItem(key);
		try {
			json = JSON.parse(json);
		} catch(ex) { }
//		console.log('getJSON', localStorage.getItem(key), json, json || def);
		return json || def;
	},
};