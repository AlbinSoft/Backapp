import React, { useState, useContext, useEffect } from 'react';

import icons from './icons.jsx';
import { ico_location } from './icons.jsx'; // TODO why does not work?
import PlacesContext    from './ctx-places.jsx';
import BackupsContext   from './ctx-backups.jsx';
import MoreInfo         from './moreinfo.jsx';

const BackupsCard = (props) => {

	const backup = props.backup;

	const ctxp = useContext(PlacesContext);
	const ctxb = useContext(BackupsContext);

	const [showinfo, setShowInfo] = useState(false); // !props.short
	const [moreinfo, setMoreInfo] = useState(null);

	useEffect(() => {
		setShowInfo(!showinfo);
		if(showinfo) {
		//	const sources = ctxb.filterBySource(place.id_place);
		//	const targets = ctxb.filterByTarget(place.id_place);
		//	setMoreInfo(
		//		<MoreInfo sources={sources} targets={targets} close={ () => setShowInfo(false) } />
		//	);
		}
	}, [ctxp.redState, props.short]);

	const src = ctxp.redState.get(backup.id_place_src);
	const trg = ctxp.redState.get(backup.id_place_trg);

	if(showinfo) {
		return <li key={backup.id_backup} className="cards_item">
			<p className="cards_item_title">{backup.name}</p>
			<p className="cards_item_fld loc"><em>Source</em>    {src.name}</p>
			<p className="cards_item_fld dvc"><em>Target</em>    {trg.name}</p>
			<p className="cards_item_fld drv"><em>Agent</em>     {backup.agent}</p>
			<p className="cards_item_fld pth"><em>Frequency</em> {backup.frequency}</p>
			<p className="cards_item_btns">
				<a onClick={ e => props.addBackupEditer(backup.id_backup) }>edit</a>
				<a onClick={ e => props.dupBackup(backup.id_backup)       }>clone</a>
				<a onClick={ e => props.remBackup(backup.id_backup)       }>remove</a>
				<a onClick={ e => setShowInfo(!showinfo)                    }>info</a>
			</p>
		</li>
	} else {
		return <li key={backup.id_backup} className="cards_item">
			<p className="cards_item_title">{backup.name}</p>
			<p className="cards_item_sub">Source:</p>
			<p className="cards_item_fld loc"><em>Location</em> {src.location}</p>
			<p className="cards_item_fld dvc"><em>Device</em>   {src.device}</p>
			<p className="cards_item_fld drv"><em>Drive</em>    {src.drive}</p>
			<p className="cards_item_fld pth"><em>Path</em>     {src.path}</p>
			<p className="cards_item_sub">Target:</p>
			<p className="cards_item_fld loc"><em>Location</em> {trg.location}</p>
			<p className="cards_item_fld dvc"><em>Device</em>   {trg.device}</p>
			<p className="cards_item_fld drv"><em>Drive</em>    {trg.drive}</p>
			<p className="cards_item_fld pth"><em>Path</em>     {trg.path}</p>
			<p className="cards_item_sub">Details:</p>
			<p className="cards_item_fld drv"><em>Agent</em>     {backup.agent}</p>
			<p className="cards_item_fld pth"><em>Frequency</em> {backup.frequency}</p>
			{ moreinfo }
			<p className="cards_item_btns">
				<a onClick={ e => props.addBackupEditer(backup.id_backup) }>edit</a>
				<a onClick={ e => props.dupBackup(backup.id_backup)       }>clone</a>
				<a onClick={ e => props.remBackup(backup.id_backup)       }>remove</a>
				<a onClick={ e => setShowInfo(!showinfo)                    }>info</a>
			</p>
		</li>;
	}
};

export default BackupsCard;
