import React, { useState, useReducer, useContext, useEffect } from 'react';

import PlacesContext from './ctx-places.jsx';

const PlacesAdd = (props) => {

	const ctx = useContext(PlacesContext);

	const [ isValid, setValid ] = useState(false);

	const [ info, setInfo ] = useReducer((prevState, action) => {
		var state = {...prevState};
		switch(action.type) {
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
		ctx.dispatchRedState({ type: info.id_place ? 'UPD' : 'ADD', item: {
			id_place: info.id_place || 'new'+Math.random().toString().substr(2),
			location: info.location,
			device:   info.device,
			drive:    info.drive,
			path:     info.path,
		}});
		props.onClose();
	};

	const cancel = () => {
		props.onClose();
	};

	return <li className="places_add">
		<input value={ info.location } onChange={ evt => setInfo({ type: 'set_location', value: evt.target.value }) } />
		<input value={ info.device   } onChange={ evt => setInfo({ type: 'set_device',   value: evt.target.value }) } />
		<input value={ info.drive    } onChange={ evt => setInfo({ type: 'set_drive',    value: evt.target.value }) } />
		<input value={ info.path     } onChange={ evt => setInfo({ type: 'set_path',     value: evt.target.value }) } />

		<button onClick={ save   } disabled={!isValid}>save</button>
		<button onClick={ cancel }>cancel</button>
	</li>;
}

export default PlacesAdd;
