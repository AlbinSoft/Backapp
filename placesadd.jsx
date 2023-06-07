import React, { useState, useReducer, useContext, useEffect } from 'react';

import PlacesContext from './ctx-places.jsx';

const PlacesAdd = (props) => {

	const place = props.place;

	const ctxp = useContext(PlacesContext);

	const [ isValid, setValid ] = useState(false);

	const [ info, setInfo ] = useReducer((prevState, action) => {
		var state = {...prevState};
		switch(action.type) {
			case 'set_name':
				state.name = action.value;
			break;
			case 'set_location':
				state.location = action.value;
			break;
			case 'set_device':
				state.device = action.value;
			break;
			case 'set_drive':
				state.drive = action.value;
			break;
			case 'set_path':
				state.path = action.value;
			break;
			case 'set_notes':
				state.notes = action.value;
			break;
		}
		return state;
	}, props.place || {});

	useEffect(() => {
		let isValid = true;
		if(!info.location || info.location.trim().lenght===0) isValid = false;

		setValid(isValid);
console.log('isValid', isValid);
	}, [ info ]);

	const save = () => {
		ctxp.dispatchRedState({ type: info.id_place ? 'UPD' : 'ADD', item: {
			id_place: info.id_place || 'new'+Math.random().toString().substr(2),
			name:     info.name,
			location: info.location,
			device:   info.device,
			drive:    info.drive,
			path:     info.path,
			notes:    info.notes,
		}});
		props.onClose();
	};

	const cancel = () => {
		props.onClose();
	};

	return <li className="places_item add">
		<p className="places_item_title">{ props.place ? 'Modifing' : 'Adding'} &hellip;</p>
		<p className="places_item_fld tit"><em>Name</em>     <input value={ info.name     } onChange={ evt => setInfo({ type: 'set_name',     value: evt.target.value }) } /></p>
		<p className="places_item_fld loc"><em>Location</em> <input list="locations" value={ info.location } list="locations" onChange={ evt => setInfo({ type: 'set_location', value: evt.target.value }) } /></p>
		<p className="places_item_fld dvc"><em>Device</em>   <input list="devices"   value={ info.device   } list="devices"   onChange={ evt => setInfo({ type: 'set_device',   value: evt.target.value }) } /></p>
		<p className="places_item_fld drv"><em>Drive</em>    <input list="drives"    value={ info.drive    } list="drives"    onChange={ evt => setInfo({ type: 'set_drive',    value: evt.target.value }) } /></p>
		<p className="places_item_fld pth"><em>Path</em>     <input list="paths"     value={ info.path     } list="paths"     onChange={ evt => setInfo({ type: 'set_path',     value: evt.target.value }) } /></p>
		<p className="places_item_btns">
			<button onClick={ save   } disabled={!isValid}>save</button>
			<button onClick={ cancel }>cancel</button>
		</p>
	</li>;
}

export default PlacesAdd;
