import React, { useState, useContext, useEffect } from 'react';

import icons from './icons.jsx';
import { ico_location } from './icons.jsx'; // TODO why does not work?
import PlacesContext    from './ctx-places.jsx';
import BackupsContext   from './ctx-backups.jsx';
import BackupsAdd       from './backupsadd.jsx';
import BackupsRow       from './backupsrow.jsx';
import BackupsCard      from './backupscard.jsx';
import LocalStorage     from './localstorage.jsx';


const Backups = (props) => {

	const ctxp = useContext(PlacesContext);
	const ctxb = useContext(BackupsContext);

	const mquery = window.matchMedia('(min-width: 1024px)');

	const [adding,  setAdding]  = useState([]);
	const [editing, setEditing] = useState([]);
	const [filters, setFilters] = useState(LocalStorage.getJSON('backups_filters', {}));
	const [rowed,   setRowed]   = useState(mquery.matches);
	const [short,   setShort]   = useState(true);
	const [backups, setBackups] = useState(ctxb.getBackups());

	mquery.onchange = () => setRowed(mquery.matches);

	const addBackupAdder = () => {
		const temp  = [...adding];
		const tkey  = 'k'+Math.random().toString().substr(2);
		temp.push( <BackupsAdd key={tkey} onClose={v => remBackupAdder(tkey)} /> );
		setAdding(temp);
	};

	const remBackupAdder = (tkey) => {
		let temp  = [...adding];
		temp = temp.filter(item => item.key!==tkey);
		setAdding(temp);
	};

	const addBackupEditer = (id_backup) => {
		const temp  = [...editing];
		temp.push(id_backup);
		setEditing(temp);
	};

	const remBackupEditer = (id_backup) => {
		let temp  = [...editing];
		temp = temp.filter(item => item!=id_backup);
		setEditing(temp);
	};

	const dupBackup = (id_backup) => {
		ctxb.dispatchRedState({ type: 'DUP', id_backup: id_backup });
	};

	const remBackup = (id_backup) => {
		if(confirm('Are you sure?')) {
			ctxb.dispatchRedState({ type: 'DEL', id_backup: id_backup });
		}
	};

	const setFilter = (fld, evt) => {
		const f = { [fld]: evt.target.value ? evt.target.value : null };
		setFilters({ ...filters, ...f });
	};

	const keyup = (evt) => {
		if(evt.keyCode==73 && evt.ctrlKey) {
			evt.preventDefault();
			addBackupAdder();
		}
	};

	useEffect(() => {
		window.addEventListener('keydown', keyup);

		return () => {
			window.removeEventListener('keyup', keyup);
		};
	}, []);

	useEffect(() => {
		setBackups(ctxb.filterBy(filters));
	}, [ctxb.redState, filters]);

	useEffect(() => {
		LocalStorage.setJSON('backups_filters', filters);
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
			<p className="places_fltr">
				<label htmlFor="path">Style</label>
				<button className={ short ? 'on' : 'off' } onClick={ e => { e.preventDefault(); setShort(!short); } }>Compact</button>
			</p>
		</form>
		<ul className={ rowed ? 'rows_list' :'cards_list' }>
			{ adding }
			{ backups.map(backup => {
				if(!backup) return null;
				if(backup.alter==='del') return null;
				if(editing.includes(backup.id_backup)) {
					return <BackupsAdd
						 key     = {backup.id_backup}
						 backup  = {backup}
						 onClose = {v => remBackupEditer(backup.id_backup)}
					/>
				} else {
					// const params =
					if(rowed) return <BackupsRow
							key       = {backup.id_backup}
							backup    = {backup} short={short}
							addBackupEditer = {addBackupEditer}
							dupBackup = {dupBackup}
							remBackup = {remBackup}
						/>
					return <BackupsCard
						key       = {backup.id_backup}
						backup    = {backup} short={short}
						addBackupEditer = {addBackupEditer}
						dupBackup = {dupBackup}
						remBackup = {remBackup}
					/>
				}
				return null;
			}) }
		</ul>
		<button className="places_btnadd" onClick={addBackupAdder}>+</button>
	</div>;
};

export { Backups };
