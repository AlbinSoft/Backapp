import React, { useState, useContext, useEffect } from 'react';

import icons from './icons.jsx';
import { ico_location } from './icons.jsx'; // TODO why does not work?
import PlacesContext    from './ctx-places.jsx';
import PlacesAdd        from './placesadd.jsx';

const Places = (props) => {

	const ctx = useContext(PlacesContext);

	const [adding, setAdding] = useState([]);
	const [editing, setEditing] = useState([]);


	const remPlaceAdder = (tkey) => {
console.log('af remPlaceAdder', tkey, adding);
		let temp  = [...adding];
		temp = temp.filter(item => item.key!==tkey);
		setAdding(temp);
	};

	const addPlaceAdder = () => {

		const temp  = [...adding];
		const tkey  = 'k'+Math.random().toString().substr(2);
		temp.push( <PlacesAdd key={tkey} onClose={v => remPlaceAdder(tkey)} /> );
console.log('keyup create', tkey, temp);
		setAdding(temp);
	};

	const keyup = (evt) => {
		if(evt.keyCode==65 && evt.ctrlKey) {
			evt.preventDefault();
console.log('keyup enter', adding);
			addPlaceAdder();
		}
	};

	const remPlaceEditer = (id_place) => {
		let temp  = [...editing];
		temp = temp.filter(item => {
			return item!=id_place;
		});
		setEditing(temp);
	};

	const addPlaceEditer = (id_place) => {
		const temp  = [...editing];
		temp.push(id_place);
		setEditing(temp);
	};

	const remPlace = (id_place) => {
		ctx.dispatchRedState({ type: 'DEL', id: id_place });
	};

	useEffect(() => {
		window.addEventListener('keydown', keyup);

		return () => {
			window.removeEventListener('keyup', keyup);
		};
	}, []);

	return <div className="places_cont">
		<ul className="places_list">
			{ adding }
			{ ctx.redState.map(place => {
				if(place.alter==='del') return null;
				if(editing.includes(place.id_place)) {
					return <PlacesAdd key={place.id_place} place={place} onClose={v => remPlaceEditer(place.id_place)} />
				}
				return <li key={place.id_place} className="places_item">
					<p className="places_item_loc">{icons.ico_location}{place.location}</p>
					<p className="places_item_dvc">{icons.ico_location}{place.device}</p>
					<p className="places_item_drv">{icons.ico_disk    }{place.drive}</p>
					<p className="places_item_pth">{icons.ico_location}{place.path}</p>
					<a onClick={ e => addPlaceEditer(place.id_place) }>editar</a>
					<a onClick={ e => remPlace(place.id_place)       }>eliminar</a>
				</li>
			}) }
		</ul>
		<button className="places_btnadd" onClick={addPlaceAdder}>+</button>
	</div>;
};

export { Places };
