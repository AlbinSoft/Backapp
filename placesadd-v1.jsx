import React, { useState, useContext, useEffect } from 'react';

import PlacesContext    from './ctx-places.jsx';

const PlacesAdd = (props) => {

	const ctx = useContext(PlacesContext);

	const [location, setLocation] = useState('');

	const save = () => {
		ctx.dispatchRedState({ type: 'ADD', item: {
			location,
		}});
		props.onClose();
	};

	const cancel = () => {
		props.onClose();
	};

	return <li className="places_add">
		<input value={ location } onChange={ evt => setLocation(evt.target.value) } />

		<button onClick={ save   }>save</button>
		<button onClick={ cancel }>cancel</button>
	</li>;
}

export default PlacesAdd;
