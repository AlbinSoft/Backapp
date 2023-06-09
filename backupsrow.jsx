import React, { useState, useContext, useEffect } from 'react';

import icons from './icons.jsx';
import { ico_location } from './icons.jsx'; // TODO why does not work?
import PlacesContext    from './ctx-places.jsx';
import BackupsContext   from './ctx-backups.jsx';
import MoreInfo         from './moreinfo.jsx';

const BackupsRow = (props) => {

	const backup = props.backup;

	const ctxp = useContext(PlacesContext);
	const ctxb = useContext(BackupsContext);

	const src = ctxp.redState.get(backup.id_place_src);
	const trg = ctxp.redState.get(backup.id_place_trg);

	return <li key={backup.id_backup} className="rows_item">
		<p className="rows_item_title">
			{backup.name} #{backup.id_backup}
			<span className="rows_item_btns">
				<a onClick={ e => props.addBackupEditer(backup.id_backup) }>edit</a>
				<a onClick={ e => props.dupBackup(backup.id_backup)       }>clone</a>
				<a onClick={ e => props.remBackup(backup.id_backup)       }>remove</a>
			</span>
		</p>
		<div className="rows_item_headers">
			<span>Name</span>
			<span>Location</span>
			<span>Device</span>
			<span>Drive</span>
			<span>Path</span>
			<span>Agent</span>
			<span>Frequency</span>
		</div>
		<div className="rows_item_values">
			<span>{src.name}</span>
			<span>{src.location}</span>
			<span>{src.device}</span>
			<span>{src.drive}</span>
			<span>{src.path}</span>
			<span>{backup.agent}</span>
			<span>{backup.frequency}</span>
		</div>
		<div className="rows_item_values">
			<span>{trg.name}</span>
			<span>{trg.location}</span>
			<span>{trg.device}</span>
			<span>{trg.drive}</span>
			<span>{trg.path}</span>
		</div>
	</li>;
};

export default BackupsRow;
