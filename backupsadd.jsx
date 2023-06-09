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
			case 'set_name':
				state.name = action.value;
			break;
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
			case 'set_notes':
				state.notes = action.value;
			break;
		}
		return state;
	}, props.backup || {});
//	console.log('props.backup', props.backup, info);

	useEffect(() => {
		let isValid = true;
		if(!info.id_place_src) isValid = false;
		if(!info.id_place_trg) isValid = false;
		setValid(isValid);
	}, [ info ]);

	const save = () => {
//	console.log('save', ctx_backups);
		ctx_backups.dispatchRedState({ type: info.id_backup ? 'UPD' : 'ADD', item: {
			id_backup:    info.id_backup || 'new'+Math.random().toString().substr(2),
			name:         info.name,
			id_place_src: info.id_place_src,
			id_place_trg: info.id_place_trg,
			agent:        info.agent,
			frequency:    info.frequency,
			notes:        info.notes,
		}});
		props.onClose();
	};

	const cancel = () => {
		props.onClose();
	};

	return <li className="cards_item add">
		<p className="cards_item_title">{ props.backup ? 'Modifing' : 'Adding'} &hellip;</p>
		<p className="cards_item_fld tit"><em>Name</em>
			<input value={ info.name      } onChange={ evt => setInfo({ type: 'set_name', value: evt.target.value }) } />
		</p>
		<p className="cards_item_fld src"><em>Source</em>
			<select value={ info.id_place_src } onChange={ evt => setInfo({ type: 'set_place_src', value: evt.target.value }) }>
				<option value={0}>-</option>
				{ ctx_places.getPlaces().map(place => {
					return <option value={place.id_place}>{place.name}</option>
				}) }
			</select>
		</p>
		<p className="cards_item_fld trg"><em>Tarjet</em>
			<select value={ info.id_place_trg } onChange={ evt => setInfo({ type: 'set_place_trg', value: evt.target.value }) }>
				<option value={0}>-</option>
				{ ctx_places.getPlaces().map(place => {
					return <option value={place.id_place}>{place.name}</option>
				}) }
			</select>
		</p>
		<p className="cards_item_fld agt"><em>Agent</em>
			<input value={ info.agent     } onChange={ evt => setInfo({ type: 'set_agent',     value: evt.target.value }) } />
		</p>
		<p className="cards_item_fld frq"><em>Frequency</em>
			<input value={ info.frequency } onChange={ evt => setInfo({ type: 'set_frequency', value: evt.target.value }) } />
		</p>
		<p className="cards_item_btns">
			<button onClick={ save   } disabled={!isValid}>save</button>
			<button onClick={ cancel }>cancel</button>
		</p>
	</li>;
}

export default BackupsAdd;
