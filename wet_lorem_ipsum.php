<?php
/* $LastChangedRevision: $ */
$plugin['version'] = '0.2';
$plugin['author'] = 'Robert Wetzlmayr';
$plugin['author_uri'] = 'http://wetzlmayr.com/';
$plugin['description'] = 'Create new articles as test data';
$plugin['type'] = 3;

@include_once('zem_tpl.php');

if (0) {
?>
# --- BEGIN PLUGIN HELP ---

h3. Purpose

Creates articles with randomized content to serve as test data during performance reviews and the like.

h3. How to use

# Navigate to "Content > wet_lorem_ipsum":?event=wet_lorem_ipsum.
# Enter the desired amount of new articles.
# Hit "Save".
# Sit and wait. Depending on the performance of your MySQL server and the amount of new articles it can take several minutes until the operation finishes.

h3. Licence

This plugin is released under the "Gnu General Public Licence":http://www.gnu.org/licenses/gpl.txt.

# --- END PLUGIN HELP ---

<?php
}

# --- BEGIN PLUGIN CODE ---

class wet_lorem_ipsum
{
	static private $words = array(
		'Lorem',  'ipsum', 'dolor', 'sit', 'amet', 'consectetur', 'adipiscing', 'elit'
	);

	static private $grafs = array(
		'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Nunc vitae nisl sed justo porttitor faucibus in sed elit. Pellentesque venenatis hendrerit imperdiet. Sed at volutpat nisi. Nulla auctor, nisi condimentum sagittis consequat, magna felis condimentum lacus, eu imperdiet odio leo ut diam. Nullam vel quam elit. Donec ipsum sem, bibendum sit amet tincidunt quis, suscipit ut ligula. Maecenas vehicula, leo id eleifend facilisis, augue nibh sodales ante, eu aliquam dui lectus et nisi. Aliquam quis metus sed nisi venenatis fringilla. Sed eget justo felis, eget sodales erat. Pellentesque id est ac felis imperdiet ultrices. Pellentesque fermentum euismod elementum. Proin turpis velit, imperdiet et interdum a, fringilla vel sem.',
		'Sed tincidunt porta sollicitudin. Duis diam enim, molestie ut scelerisque ut, rutrum eu augue. Suspendisse velit nunc, malesuada et luctus non, scelerisque et turpis. Sed turpis ligula, gravida at vestibulum sed, semper ut felis. Fusce turpis diam, adipiscing commodo elementum ac, porttitor vitae mauris. Sed quis urna sed nulla tincidunt condimentum. Proin dignissim, sapien eget vulputate commodo, purus arcu porttitor mauris, ac sollicitudin diam leo et mauris.',
		'Pellentesque nec velit in erat lacinia sagittis. Donec eu lacus non quam vulputate sollicitudin. Morbi sodales tempor diam, vel tincidunt felis feugiat vestibulum. Curabitur nec dapibus ligula. Lorem ipsum dolor sit amet, consectetur adipiscing elit. Vestibulum neque sapien, aliquam quis interdum vel, dignissim in risus. Nulla nec est sed tortor pharetra tristique aliquet vel lacus. Nam at ipsum velit.',
		'Nam cursus elit dignissim mi pharetra nec ullamcorper lectus tristique. Duis vitae lacus sit amet tellus venenatis faucibus at ut lectus. Etiam porttitor neque at eros laoreet sed pulvinar augue facilisis. Integer condimentum orci velit. Duis interdum metus sit amet sapien imperdiet at egestas nisi pretium. Nulla facilisi. Maecenas porta scelerisque magna non imperdiet. Nullam commodo ornare consequat. Phasellus pulvinar porttitor diam, quis convallis erat pharetra ac. Phasellus nec leo justo.',
		'Donec congue venenatis erat at imperdiet. In egestas, lacus et pretium molestie, orci turpis convallis justo, consequat egestas ante felis nec magna. Aenean augue risus, bibendum ut auctor non, tincidunt commodo elit. Duis at metus vel urna ultrices pulvinar. Nullam at fermentum turpis. Aenean eget ornare lectus. Donec ac est at diam rutrum imperdiet. Maecenas sit amet placerat magna. Pellentesque eleifend iaculis sodales. Vivamus sodales justo vel erat euismod non lobortis enim suscipit. Integer hendrerit pharetra mauris id imperdiet. Ut consectetur elit eu lorem tempor viverra. In hac habitasse platea dictumst. In nec libero ut quam molestie venenatis. Sed et ultricies felis. Nullam dictum lorem in sem congue eget fringilla velit fermentum. ',
	);

	static private $section_names;

	/**
	 * Hook UI, setup privileges
	 */
	function __construct()
	{
		if (txpinterface == 'admin') {
			add_privs('wet_lorem_ipsum', '1,2');
			register_tab('content', 'wet_lorem_ipsum', gTxt('wet_lorem_ipsum'));
			register_callback(array(__CLASS__, 'ui'), 'wet_lorem_ipsum');

			self::$section_names = safe_column('name', 'txp_section', 'name != \'default\'');
		}
	}

	/**
	 * User interface
	 */
	static function ui()
	{
		global $step;

		$message = '';
		$ok = true;
		$vars = array('articles', 'sections');

		extract(psa($vars));

		if (bouncer($step, array(
			'add_things' => true
		))) {
			foreach ($vars as $var) {
				if ($$var !== '') {
					$$var = assert_int($$var);

					// If amount is greater zero: Create new elements
					if ($$var > 0) {
						$f = 'add_'.$var;
						$ok *= self::$f($$var);
					}
					$message = ($ok) ? gTxt('ok') : array(gTxt('nok'), E_ERROR);
				}
			}
		}

		pagetop(gTxt(__CLASS__), $message);
		$out[] = hed(gTxt('wet_lorem_ipsum'), 2);
		$out[] =  inputLabel('articles', fInput('text', 'articles', $articles, '', '', '', INPUT_REGULAR, '', 'articles'), 'articles');
//	TODO	$out[] =  inputLabel('sections', fInput('text', 'sections', $sections, '', '', '', INPUT_REGULAR, '', 'sections'), 'sections');

		$out[] = graf(fInput('submit', '', gTxt('save'), 'publish')).n.
			eInput(__CLASS__).n.
			sInput('add_things');

		echo form(
			n.tag(join('', $out).n, 'section', array('class' => 'txp-edit'))
			, '', '', 'post', 'edit-form', '', __CLASS__);
	}

	/**
	 * Add new articles
	 */
	static function add_articles($amount)
	{
		global $txp_user;

		@set_time_limit(0);
		$textile = new Textpattern_Textile_Parser();

		$user = safe_escape($txp_user);
		$textile_body = $textile_excerpt = USE_TEXTILE;
		$ok = true;

		for ($i = 0; $i < $amount; $i++) {
			$Title = safe_escape(__CLASS__ . ' ' . join(' ', self::array_random(self::$words, mt_rand(2, count(self::$words)))));
			$Body = join(n.n, self::array_random(self::$grafs, mt_rand(2, count(self::$grafs))));
			$Body_html = safe_escape($textile->textileThis($Body));
			$Body = safe_escape($Body);
			$url_title = safe_escape(md5(uniqid(rand(), true)));
			$Section = safe_escape(self::array_random(self::$section_names, 1));
			$custom_1 = safe_escape(self::array_random(self::$words, 1));
			$custom_2 = safe_escape(self::array_random(self::$words, 1));
			$Keywords = safe_escape(join(', ', self::array_random(self::$words, mt_rand(2, count(self::$words)))));

			$ok *= safe_insert(
				"textpattern",
				"Title           = '$Title',
					Body            = '$Body',
					Body_html       = '$Body_html',
					Excerpt         = '',
					Excerpt_html    = '',
					Image           = '',
					Keywords        = '$Keywords',
					Status          =  ".STATUS_LIVE.",
					Posted          =  DATE_SUB(now(), INTERVAL ".mt_rand(0, 3600*24*7)." SECOND),
					Expires         =  ".NULLDATETIME.",
					AuthorID        = '$user',
					LastMod         =  0,
					LastModID       = '',
					Section         = '$Section',
					Category1       = '',
					Category2       = '',
					textile_body    = '$textile_body',
					textile_excerpt = '$textile_excerpt',
					Annotate        =  0,
					override_form   = '',
					url_title       = '$url_title',
					AnnotateInvite  = '',
					custom_1        = '$custom_1',
					custom_2        = '$custom_2',
					uid             = '".md5(uniqid(rand(), true))."',
					feed_time       = now()"
			);

		}

		return $ok;
	}

	/**
	 * Add new sections
	 */
	static function add_sections($amount)
	{
		return true;
	}

	private static function array_random($arr, $num = 1)
	{
		shuffle($arr);
		return ($num == 1) ? $arr[0] : array_slice($arr, 0, $num);
	}
}

new wet_lorem_ipsum;

# --- END PLUGIN CODE ---

?>