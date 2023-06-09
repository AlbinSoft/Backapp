import React, { useEffect, useCallback, useReducer } from 'react';
import URLs from './URLs';

const PlacesContext = React.createContext({
	redState: initPlaces,
	dispatchRedState: () => {},
	filterByLocation: () => {},
	filterByDevice:   () => {},
	filterByDrive:    () => {},
	filterByPath:     () => {},
	filterBy:         () => {},
	listLocations:    () => {},
	listDevices:      () => {},
	listDrives:       () => {},
	listPaths:        () => {},
});

const PlacesContextProvider = (props) => {

	const [redState, dispatchRedState] = useReducer(
		(prevState, action) => {
			let newState, item, value;
			switch(action.type) {
				case 'ADD':
					newState = new Map(prevState);
					newState.set(action.item.id_place, {...action.item, alter: 'ins'});
					return newState;
				break;
				case 'UPD':
					item  = prevState.get(action.item.id_place);
					value = {...item, ...action.item, alter: 'upd'};
					newState = new Map(prevState);
					newState.set(action.item.id_place, value);
					return newState;
				break;
				case 'DUP':
					item  = prevState.get(action.id_place);
					value = {...item, id_place: 'new'+Math.random().toString().substr(2), alter: 'ins'};
					newState = new Map(prevState);
					newState.set(value.id_place, value);
					return newState;
				break;
				case 'DEL':
					item  = prevState.get(action.id);
					value = {...item, alter: 'del'};
					newState = new Map(prevState);
					newState.set(action.id, value);
					return newState;
				break;
				case 'RESET':
					const oids = action.oids || [];
					const nids = Object.keys(action.nids || {});
					if(!nids.length && !oids.length) return prevState;
					let temp = [...prevState.values()];
					temp.forEach((item, idx) => {
						if(action.oids.includes(item.id_place)) {
							if(item.alter=='del') delete temp[idx]
							if(item.alter!='del') item.alter = false;
						}
						if(nids.includes(item.id_place)) {
							item.alter = false;
							item.id_place = action.nids[item.id_place];
						}
					});
					temp = temp.filter(item => item!==null);
					newState = new Map();
					temp.map(place => newState.set(place.id_place, place));
					return newState;
				break;
			}
			return prevState;
		},
		initPlaces,
	);

	const orderByName = (a, b) => {
		if(a.name<b.name) return -1;
		if(a.name>b.name) return +1;
		return 0;
	};

	const getPlaces = useCallback((params) => {
		const values = Array.from(redState.values());
		return values.sort(orderByName);
	}, [redState]);

	const filterByLocation = useCallback((id_src) => {
		const values = Array.from(redState.values()).filter(i => {
			return i.id_place_src == id_src;
		});
		return values;
	}, [redState]);

	const filterBy = useCallback((params) => {
		const values = getPlaces().filter(i => {
			let keep = true;
			if(params.location && params.location!==i.location) keep = false;
			if(params.device   && params.device  !==i.device)   keep = false;
			if(params.drive    && params.drive   !==i.drive)    keep = false;
			if(params.path     && params.path    !==i.path)     keep = false;
			return keep;
		});
		return values;
	}, [redState])

	const listLocations = useCallback(() => {
		const items = new Set();
		redState.forEach(i => items.add(i.location));
		return [...items].sort();
	}, [redState]);

	const listDevices = useCallback(() => {
		const items = new Set();
		redState.forEach(i => items.add(i.device));
		return [...items].sort();
	}, [redState]);

	const listDrives = useCallback(() => {
		const items = new Set();
		redState.forEach(i => items.add(i.drive));
		return [...items].sort();
	}, [redState]);

	const listPaths = useCallback(() => {
		const items = new Set();
		redState.forEach(i => items.add(i.path));
		return [...items].sort();
	}, [redState]);

	const value = {
		redState,
		dispatchRedState,
		getPlaces,
		filterByLocation,
		filterBy,
		listLocations,
		listDevices,
		listDrives,
		listPaths,
	};

	useEffect(() => {
		var places = getPlaces().filter(place => place.alter!==false);
		if(places.length) {
			var fdata = new FormData();
			fdata.append('action', 'set_places');
			fdata.append('places', JSON.stringify(places));
			fetch(URLs.ajax(), { body: fdata, method: 'POST' }).then(resp => resp.json()).then(data => {
				if(data.ok) {
					const oids = [];
					const nids = data.ids;
					places.forEach(place => place.id_place>0 && oids.push(place.id_place));
					if(oids || nids) {
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
