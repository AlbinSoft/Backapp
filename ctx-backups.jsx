import React, { useEffect, useReducer } from 'react';

const BackupsContext = React.createContext({
	redState: initBackups,
	dispatchRedState: () => {},
});

const BackupsContextProvider = (props) => {

	const [redState, dispatchRedState] = useReducer(
		(prevState, action) => {
console.log('dispatchState', action);
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
						if(item.id_relation==action.item.id_relation) {
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
						if(item.id_relation==action.id) {
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

	const value = {
		redState,
		dispatchRedState,
	};

	useEffect(() => {
console.log('useEffect', redState);
		var backups = redState.filter(backup => backup.alter!==false);
		if(backups.length) {
			var fdata = new FormData();
			fdata.append('action', 'set_backups');
			fdata.append('backups', JSON.stringify(backups));
			fetch('index.php', { body: fdata, method: 'POST' }).then(resp => resp.json()).then(data => {
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
