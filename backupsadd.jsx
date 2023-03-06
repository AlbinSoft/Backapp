import React, { useState, useReducer, useContext, useEffect } from 'react';

import PlacesContext  from './ctx-places.jsx';
import BackupsContext from './ctx-backups.jsx';

const BackupsAdd = (props) => {

	const ctx_places  = useContext(PlacesContext);
	const ctx_backups = useContext(BackupsContext);

	const [ isValid, setValid ] = useState(false);

	const [ info, setInfo ] = useReducer((prevState, action) => {
		var state = {...prevState};
		switch(action.type) {
			case 'set_place_src':
				state.id_place_src = action.value;
			break;
			case 'set_place_trg':
				state.id_place_trg = action.value;
			break;
			case 'set_agent':
				state.agent = action.value;
			break;
			case 'set_frequency':
				state.frequency = action.value;
			break;
		}
		return state;
	}, props.backup || {});
console.log('props.backup', props.backup, info);

	useEffect(() => {
		let isValid = true;
		if(!info.id_place_src) isValid = false;
		if(!info.id_place_trg) isValid = false;
		setValid(isValid);
	}, [ info ]);

	const save = () => {
console.log('save', ctx_backups);
		ctx_backups.dispatchRedState({ type: info.id_relation ? 'UPD' : 'ADD', item: {
			id_relation:  info.id_relation || 'new'+Math.random().toString().substr(2),
			id_place_src: info.id_place_src,
			id_place_trg: info.id_place_trg,
			agent:        info.agent,
			frequency:    info.frequency,
		}});
		props.onClose();
	};

	const cancel = () => {
		props.onClose();
	};

	return <li className="backups_add">
        <select value={ info.id_place_src } onChange={ evt => setInfo({ type: 'set_place_src', value: evt.target.value }) }>
            <option value={0}>-</option>
            { ctx_places.get_pairs().map(place => {
                return <option value={place.id}>{place.label}</option>
            }) }
        </select>
		<select value={ info.id_place_trg } onChange={ evt => setInfo({ type: 'set_place_trg', value: evt.target.value }) }>
            <option value={0}>-</option>
            { ctx_places.get_pairs().map(place => {
                return <option value={place.id}>{place.label}</option>
            }) }
        </select>
		<input value={ info.agent     } onChange={ evt => setInfo({ type: 'set_agent',     value: evt.target.value }) } />
		<input value={ info.frequency } onChange={ evt => setInfo({ type: 'set_frequency', value: evt.target.value }) } />

		<button onClick={ save   } disabled={!isValid}>save</button>
		<button onClick={ cancel }>cancel</button>
	</li>;
}

export default BackupsAdd;
