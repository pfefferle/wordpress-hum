import { registerPlugin } from '@wordpress/plugins';
import ShortlinkPanel from './shortlink-panel';

registerPlugin( 'hum-gutenberg-shortlink-panel', {
	render: ShortlinkPanel,
} );
