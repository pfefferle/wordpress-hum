/**
 * @jest-environment jsdom
 */

jest.mock( '@wordpress/plugins', () => ( {
	registerPlugin: jest.fn(),
} ) );

// Only the component identity matters here, keep the editor out of it.
jest.mock( '@wordpress/editor', () => ( {
	PluginDocumentSettingPanel: () => null,
} ) );

import { registerPlugin } from '@wordpress/plugins';
import ShortlinkPanel from '../shortlink-panel';

describe( 'editor script', () => {
	test( 'registers the shortlink panel as an editor plugin', () => {
		require( '../index' );

		expect( registerPlugin ).toHaveBeenCalledTimes( 1 );
		expect( registerPlugin ).toHaveBeenCalledWith(
			'hum-gutenberg-shortlink-panel',
			{ render: ShortlinkPanel }
		);
	} );
} );
