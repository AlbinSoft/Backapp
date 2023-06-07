import React, { useContext } from 'react';
import ReactDOM from 'react-dom';
import { createRoot } from 'react-dom/client';
import { createBrowserRouter, RouterProvider } from 'react-router-dom';
import { NavLink, Outlet } from 'react-router-dom';
import URLs from './URLs';

import PlacesContext              from './ctx-places.jsx';
import BackupsContext             from './ctx-backups.jsx';
import { PlacesContextProvider  } from './ctx-places.jsx';
import { BackupsContextProvider } from './ctx-backups.jsx';

import { Places }  from './places.jsx';
import { Backups } from './backups.jsx';


const GenericLayout = (props) => {
	const ctxp = useContext(PlacesContext);
//	const ctxb = useContext(BackupsContext);

	const cname = ({isActive}) => `${isActive ? 'sel' : 'uns'}`;

	const locations  = ctxp.listLocations();
	const devices    = ctxp.listDevices();
	const drives     = ctxp.listDrives();
	const paths      = ctxp.listPaths();
	const hlocations = [];
	const hdevices   = [];
	const hdrives    = [];
	const hpaths     = [];
	locations.forEach(i => hlocations.push(<option key={i} value={i} />));
	devices.forEach(i => hdevices.push(<option key={i} value={i} />));
	drives.forEach(i => hdrives.push(<option key={i} value={i} />));
	paths.forEach(i => hpaths.push(<option key={i} value={i} />));

	return <React.Fragment>
		<header className="header">
			<img className="header_logo" src={URLs.logo()} width="60" height="60" alt="BackApp" />
			<nav className="header_nav">
				<NavLink to={URLs.places()}  className={cname}>Places</NavLink>
				<NavLink to={URLs.backups()} className={cname}>Backups</NavLink>
			</nav>
		</header>
		<Outlet />
		<datalist id="locations">{hlocations}</datalist>
		<datalist id="devices"  >{hdevices}</datalist>
		<datalist id="drives"   >{hdrives}</datalist>
		<datalist id="paths"    >{hpaths}</datalist>
	</React.Fragment>;
}


const router = createBrowserRouter([
	{ path: '/backups/', element: <GenericLayout />, children: [
		{ path: '/backups/',         element: <Places  /> },
		{ path: '/backups/places/',  element: <Places  /> },
		{ path: '/backups/backups/', element: <Backups /> },
	] },
]);

class App extends React.Component {
	constructor(props) {
		super(props);
	}

	render() {
		return <PlacesContextProvider>
			<BackupsContextProvider>
				<RouterProvider router={router}></RouterProvider>
			</BackupsContextProvider>
		</PlacesContextProvider>;
	}
}

const container = document.getElementById('react-root');
const root = createRoot(container);
root.render(<App />);