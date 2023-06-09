import React, { useState, useContext, useEffect } from 'react';

import icons from './icons.jsx';
import { ico_location } from './icons.jsx'; // TODO why does not work?
import PlacesContext    from './ctx-places.jsx';
import BackupsContext   from './ctx-backups.jsx';
import PlacesCard       from './placescard.jsx';
import PlacesAdd        from './placesadd.jsx';
import LocalStorage     from './localstorage.jsx';


const Places = (props) => {

	const ctxp = useContext(PlacesContext);
	const ctxb = useContext(BackupsContext);

	const [adding,  setAdding]  = useState([]);
	const [editing, setEditing] = useState([]);
	const [filters, setFilters] = useState(LocalStorage.getJSON('places_filters', {}));
	const [places,  setPlaces]  = useState(ctxp.getPlaces());

console.log('filters', filters);

	const addPlaceAdder = () => {
		const temp  = [...adding];
		const tkey  = 'k'+Math.random().toString().substr(2);
		temp.push( <PlacesAdd key={tkey} onClose={v => remPlaceAdder(tkey)} /> );
		setAdding(temp);
	};

	const remPlaceAdder = (tkey) => {
		let temp  = [...adding];
		temp = temp.filter(item => item.key!==tkey);
		setAdding(temp);
	};

	const addPlaceEditer = (id_place) => {
		const temp  = [...editing];
		temp.push(id_place);
		setEditing(temp);
	};

	const remPlaceEditer = (id_place) => {
		let temp  = [...editing];
		temp = temp.filter(item => {
			return item!=id_place;
		});
		setEditing(temp);
	};

	const dupPlace = (id_place) => {
		ctxp.dispatchRedState({ type: 'DUP', id_place: id_place });
	};

	const remPlace = (id_place) => {
		if(confirm('Are you sure?')) {
			ctxp.dispatchRedState({ type: 'DEL', id: id_place });
		}
	};

	const setFilter = (fld, evt) => {
		const f = { [fld]: evt.target.value ? evt.target.value : null };
		setFilters({ ...filters, ...f });
	};

	const keyup = (evt) => {
		if(evt.keyCode==73 && evt.ctrlKey) {
			evt.preventDefault();
			addPlaceAdder();
		}
	};

	useEffect(() => {
		window.addEventListener('keydown', keyup);

		return () => {
			window.removeEventListener('keyup', keyup);
		};
	}, []);

	useEffect(() => {
		setPlaces(ctxp.filterBy(filters));
	}, [ctxp.redState, filters]);

	useEffect(() => {
		LocalStorage.setJSON('places_filters', filters);
	}, [filters]);

	const locations  = ctxp.listLocations();
	const devices    = ctxp.listDevices();
	const drives     = ctxp.listDrives();
	const paths      = ctxp.listPaths();
	const hlocations = [ <option key="all" value="">- All -</option> ];
	const hdevices   = [ <option key="all" value="">- All -</option> ];
	const hdrives    = [ <option key="all" value="">- All -</option> ];
	const hpaths     = [ <option key="all" value="">- All -</option> ];
	locations.forEach(i => hlocations.push(<option key={i} value={i}>{i}</option>));
	devices.forEach(i => hdevices.push(<option key={i} value={i}>{i}</option>));
	drives.forEach(i => hdrives.push(<option key={i} value={i}>{i}</option>));
	paths.forEach(i => hpaths.push(<option key={i} value={i}>{i}</option>));

	return <div className="places_cont">
		<form className="places_fltrs">
			<p className="places_fltr">
				<label htmlFor="location">Location</label>
				<select id="location" onChange={ e => setFilter('location', e) } value={filters.location}>{hlocations}</select>
			</p>
			<p className="places_fltr">
				<label htmlFor="device">Device</label>
				<select id="device"   onChange={ e => setFilter('device',   e) } value={filters.device}  >{hdevices}</select>
			</p>
			<p className="places_fltr">
				<label htmlFor="drive">Drive</label>
				<select id="drive"    onChange={ e => setFilter('drive',    e) } value={filters.drive}   >{hdrives}</select>
			</p>
			<p className="places_fltr">
				<label htmlFor="path">Path</label>
				<select id="path"     onChange={ e => setFilter('path',     e) } value={filters.path}    >{hpaths}</select>
			</p>
		</form>
		<ul className="cards_list">
			{ adding }
			{ places.map(place => {
				if(!place) return null;
				if(place.alter==='del') return null;
				if(editing.includes(place.id_place)) {
					return <PlacesAdd
						key     = {place.id_place}
						place   = {place}
						onClose = {v => remPlaceEditer(place.id_place)}
					/>
				} else {
					return <PlacesCard
						key      = {place.id_place}
						place    = {place}
						addPlaceEditer={addPlaceEditer}
						dupPlace = {dupPlace}
						remPlace = {remPlace}
					/>
				}
				return null;
			}) }
		</ul>
		<button className="places_btnadd" onClick={addPlaceAdder}>+</button>
	</div>;
};

export { Places };
