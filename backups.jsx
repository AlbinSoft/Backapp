import React, { useState, useContext, useEffect } from 'react';

import icons from './icons.jsx';
import { ico_location } from './icons.jsx'; // TODO why does not work?
import BackupsContext    from './ctx-backups.jsx';
import BackupsAdd        from './backupsadd.jsx';

const Backups = (props) => {

	const ctx = useContext(BackupsContext);

	const [adding, setAdding] = useState([]);
	const [editing, setEditing] = useState([]);


	const remBackupAdder = (tkey) => {
console.log('af remBackupsAdder', tkey, adding);
		let temp  = [...adding];
		temp = temp.filter(item => item.key!==tkey);
		setAdding(temp);
	};

	const addBackupAdder = () => {

		const temp  = [...adding];
		const tkey  = 'k'+Math.random().toString().substr(2);
		temp.push( <BackupsAdd key={tkey} onClose={v => remBackupAdder(tkey)} /> );
console.log('keyup create', tkey, temp);
		setAdding(temp);
	};

	const keyup = (evt) => {
		if(evt.keyCode==65 && evt.ctrlKey) {
			evt.preventDefault();
console.log('keyup enter', adding);
			addBackupsAdder();
		}
	};

	const remBackupEditer = (id_relation) => {
		let temp  = [...editing];
		temp = temp.filter(item => {
			return item!=id_relation;
		});
		setEditing(temp);
	};

	const addBackupEditer = (id_relation) => {
		const temp  = [...editing];
		temp.push(id_relation);
		setEditing(temp);
	};

	const remBackup = (id_relation) => {
		ctx.dispatchRedState({ type: 'DEL', id: id_relation });
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
			{ ctx.redState.map(backup => {
				if(backup.alter==='del') return null;
				if(editing.includes(backup.id_relation)) {
					return <BackupsAdd key={backup.id_relation} backup={backup} onClose={v => remBackupEditer(backup.id_relation)} />
				}
				return <li key={backup.id_relation} className="places_item">
					<p className="places_item_loc">{icons.ico_location}{backup.location_src}</p>
					<p className="places_item_dvc">{icons.ico_location}{backup.device_src}</p>
					<p className="places_item_drv">{icons.ico_disk    }{backup.drive_src}</p>
					<p className="places_item_pth">{icons.ico_location}{backup.path_src}</p>
                    <hr/>
					<p className="places_item_loc">{icons.ico_location}{backup.location_trg}</p>
					<p className="places_item_dvc">{icons.ico_location}{backup.device_trg}</p>
					<p className="places_item_drv">{icons.ico_disk    }{backup.drive_trg}</p>
					<p className="places_item_pth">{icons.ico_location}{backup.path_trg}</p>
					<a onClick={ e => addBackupEditer(backup.id_relation) }>editar</a>
					<a onClick={ e => remBackup(backup.id_relation)       }>eliminar</a>
				</li>
			}) }
		</ul>
		<button className="places_btnadd" onClick={addBackupAdder}>+</button>
	</div>;
};

export { Backups };
