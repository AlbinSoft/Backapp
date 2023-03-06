import React, { useEffect, useReducer } from 'react';

const PlacesContext = React.createContext({
	redState: initPlaces,
	dispatchRedState: () => {},
});

const PlacesContextProvider = (props) => {

	const [redState, dispatchRedState] = useReducer(
		(prevState, action) => {
			let newState;
			switch(action.type) {
				case 'ADD':
					action.item.alter = 'ins';
					newState = [...prevState];
					newState.push(action.item);
					return newState;
				break;
				case 'UPD':
					newState = [...prevState];
					newState = newState.map(item => {
						if(item.id_place==action.item.id_place) {
							if(!item.alter || item.alter===false) {
								item.alter = 'upd';
							}
							return {...item, ...action.item};
						}
						return item;
					});
					return newState;
				break;
				case 'DEL':
					newState = [...prevState];
					newState = newState.map(item => {
						if(item.id_place==action.id) {
							if(item!=='ins') {
								item.alter = 'del';
							}
						}
						return item;
					});
					return newState;
				break;
				case 'RESET':
					const nids = Object.keys(action.nids || {});
					newState = [...prevState];
					newState = newState.map(item => {
						if(action.oids.includes(item.id_place)) { if(item.alter!='del') item.alter = false; else return null; }
						if(nids.includes(item.id_place)) { item.alter = false; item.id_place = action.nids[item.id_place]; }
						return item;
					}).filter(item => item!==null);
					return newState;
				break;
			}
			return prevState;
		},
		initPlaces,
	);

	const value = {
		redState,
		dispatchRedState,
		get_pairs: function () {
			const pairs = [];
			redState.map(place => 
				pairs.push({ id: place.id_place, label: place.location+' '+place.device+' '+place.drive+' '+place.path })
			);
			return pairs;
		}
	};

	useEffect(() => {
		var places = redState.filter(place => place.alter!==false);
		if(places.length) {
			var fdata = new FormData();
			fdata.append('action', 'set_places');
			fdata.append('places', JSON.stringify(places));
			fetch('index.php', { body: fdata, method: 'POST' }).then(resp => resp.json()).then(data => {
				if(data.ok) {
					const oids = [];
					const nids = data.ids;
					places.forEach(place => place.id_place>0 && oids.push(place.id_place));
					if(oids || nids) {
console.log('oids || nids', oids, nids);
						dispatchRedState({ type: 'RESET', nids, oids });
					}
				} else {
					alert(data.msg);
				}
			});
		}
	}, [redState]);

	return (
		<PlacesContext.Provider value={value}>
			{props.children}
		</PlacesContext.Provider>
	);
};

export { PlacesContextProvider };
export default PlacesContext;
