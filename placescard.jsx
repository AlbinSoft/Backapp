import React, { useState, useContext, useEffect } from 'react';

import icons from './icons.jsx';
import { ico_location } from './icons.jsx'; // TODO why does not work?
import PlacesContext    from './ctx-places.jsx';
import BackupsContext   from './ctx-backups.jsx';
import MoreInfo         from './moreinfo.jsx';

const PlacesCard = (props) => {

	const place = props.place;

	const ctxp = useContext(PlacesContext);
	const ctxb = useContext(BackupsContext);

	const [showinfo, setShowInfo] = useState(false);
	const [moreinfo, setMoreInfo] = useState(null);

	const is_source = ctxb.isSource(place);
	const is_target = ctxb.isTarget(place);

	useEffect(() => {
		if(showinfo) {
			const sources = ctxb.filterBySource(place.id_place);
			const targets = ctxb.filterByTarget(place.id_place);
			setMoreInfo(
				<MoreInfo sources={sources} targets={targets} close={ () => setShowInfo(false) } />
			);
		}
	}, [ctxp, showinfo]);

	return <li key={place.id_place} className="places_item">
		<p className="places_item_title">{place.name} <span className={'places_item_type'+(is_source ? ' src' : '')+(is_target ? ' trg' : '')}></span></p>
		<p className="places_item_fld loc"><em>Location</em> {place.location}</p>
		<p className="places_item_fld dvc"><em>Device</em>   {place.device}</p>
		<p className="places_item_fld drv"><em>Drive</em>    {place.drive}</p>
		<p className="places_item_fld pth"><em>Path</em>     {place.path}</p>
		{ moreinfo }
		<p className="places_item_btns">
			<a onClick={ e => props.addPlaceEditer(place.id_place) }>edit</a>
			<a onClick={ e => props.remPlace(place.id_place)       }>remove</a>
			<a onClick={ e => setShowInfo(!showinfo)               }>info</a>
		</p>
	</li>;
};

export default PlacesCard;
