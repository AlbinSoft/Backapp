import React, { useEffect, useContext, useCallback, useReducer } from 'react';
import URLs from './URLs';

import PlacesContext from './ctx-places.jsx';

const BackupsContext = React.createContext({
	redState: initBackups,
	dispatchRedState: () => {},
	filterBySource:   () => {},
	filterByTarget:   () => {},
	filterBy:         () => {},
	isSource:         () => {},
	isTarget:         () => {},
});

const BackupsContextProvider = (props) => {

	const ctxp = useContext(PlacesContext);

	const [redState, dispatchRedState] = useReducer(
		(prevState, action) => {
			let newState, item, value;
			switch(action.type) {
				case 'ADD':
					newState = new Map(prevState);
					newState.set(action.item.id_relation, {...action.item, alter: 'ins'});
					return newState;
				break;
				case 'UPD':
					item  = prevState.get(action.item.id_relation);
					value = {...item, ...action.item, alter: 'upd'};
					newState = new Map(prevState);
					newState.set(action.item.id_relation, value);
					return newState;
				break;
				case 'DUP':
					item  = prevState.get(action.id_relation);
					value = {...item, id_relation: 'new'+Math.random().toString().substr(2), alter: 'ins'};
					newState = new Map(prevState);
					newState.set(action.id_relation, value);
					return newState;
				break;
				case 'DEL':
					item  = prevState.get(action.id_relation);
					value = {...item, alter: 'del'};
					newState = new Map(prevState);
					newState.set(action.id, value);
					return newState;
				break;
				case 'RESET' && action.nids.length && action.oids.length:
					const nids = Object.keys(action.nids || {});
					newState = [...prevState];
					newState = newState.map(item => {
						if(action.oids.includes(item.id_relation)) { if(item.alter!='del') item.alter = false; else return null; }
						if(nids.includes(item.id_relation)) { item.alter = false; item.id_relation = action.nids[item.id_relation]; }
						return item;
					}).filter(item => item!==null);
					return newState;
				break;
			}
			return prevState;
		},
		initBackups,
	);

	const getBackups = useCallback((params) => {
		const values = Array.from(redState.values());
		return values;
	}, [redState]);

	const filterBySource = useCallback((id_src) => {
		return new Map([...redState].filter(i => {
			return i.id_place_src == id_src;
		}));
	}, [redState]);

	const filterByTarget = useCallback((id_trg) => {
		return new Map([...redState].filter(i => {
			return i.id_place_trg == id_trg;
		}));
	}, [redState]);

	const filterBy = useCallback((params) => {
		const values = getBackups().filter(i => {
			let keep = true;
			const src = ctxp.redState.get(i.id_place_src);
			const trg = ctxp.redState.get(i.id_place_trg);
			if(params.location && ((!src || params.location!==src.location) && (!trg || trg && params.location!==trg.location))) keep = false;
			if(params.device   && ((!src || params.device  !==src.device)   && (!trg || trg && params.device  !==trg.device)))   keep = false;
			if(params.drive    && ((!src || params.drive   !==src.drive)    && (!trg || trg && params.drive   !==trg.drive)))    keep = false;
			if(params.path     && ((!src || params.path    !==src.path)     && (!trg || trg && params.path    !==trg.path)))     keep = false;
			return keep;
		});
		return values;
	}, [redState]);

	const isSource = (item) => {
		const id_place = isFinite(item) ? item : item.id_place;
		const backups  = [...redState.values()];
		const found    = backups.find(backup => backup.id_place_src==id_place);
		return typeof(found)!='undefined';
	}

	const isTarget = (item) => {
		const id_place = isFinite(item) ? item : item.id_place;
		const backups  = [...redState.values()];
		const found    = backups.find(backup => backup.id_place_trg==id_place);
		return typeof(found)!='undefined';
	}

	const value = {
		redState,
		dispatchRedState,
		getBackups,
		filterBySource,
		filterByTarget,
		filterBy,
		isSource,
		isTarget
	};

	useEffect(() => {
		var backups = getBackups().filter(backup => backup.alter!==false);
		if(backups.length) {
			var fdata = new FormData();
			fdata.append('action', 'set_backups');
			fdata.append('backups', JSON.stringify(backups));
			fetch(URLs.ajax(), { body: fdata, method: 'POST' }).then(resp => resp.json()).then(data => {
				if(data.ok) {
					const oids = [];
					const nids = data.ids;
					backups.forEach(backup => backup.id_relation>0 && oids.push(backup.id_relation));
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
		<BackupsContext.Provider value={value} >
			{props.children}
		</BackupsContext.Provider>
	);
};

export { BackupsContextProvider };
export default BackupsContext;
