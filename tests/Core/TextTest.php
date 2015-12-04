<?php
namespace Unit\Core;

use App\Text;
use Unit\AbstractTestCase;

/**
 * Class TextTest
 * @package Unit\Core
 */
class TextTest extends AbstractTestCase
{
    /**
     * @param null $text
     * @return Text
     */
    protected function mock($text = null)
    {
        return new Text($text ?: 'Test Sentence');
    }

    public function testTextCase()
    {
        $this->assertEquals((string)$this->mock()->toLowerCase(), 'test sentence');

        $this->assertEquals((string)$this->mock()->toUpperCase(), 'TEST SENTENCE');
    }

    public function testTextSentences()
    {
        $texts = [
            'Sentence1)) Sentence2',
            'Sentence1. Sentence2',
            'Sentence1' . "\n" . 'Sentence2',
            'Sentence1 :smile: Sentence2',
            'Sentence1 :smile: :another_smile: Sentence2',
            'Sentence1 =))) :some: ' . "\n" . ' Sentence2',
        ];

        foreach ($texts as $text) {
            $instance = $this->mock($text);

            foreach ($instance->sentences() as $sentence) {
                $this->assertEquals((string)$sentence, trim($sentence));
                $this->assertTrue($sentence instanceof Text);
                $this->assertTrue((bool)trim($sentence));
            }

            $this->assertTrue(is_array($instance->sentences()));
            $this->assertEquals(count($instance->sentences()), 2);
        }
    }

    public function testTextFilters()
    {
        $texts = [
            'text&)(*@# text@nickname text'                                => 'text text text',
            '@Some привет тебе чебурашка =)))'                             => 'привет тебе чебурашка',
            '@Some привет тебе чебурашка =)))' . "\n" . 'ещё одна строчка' => 'привет тебе чебурашка ещё одна строчка',
        ];

        foreach ($texts as $source => $result) {
            $this->assertEquals((string)$this->mock($source)->onlyWords(), $result);
        }
    }


    public function testTextMarkdownEscape()
    {
        $texts = [
            '_italic_'              => '\_italic\_',
            '*italic*'              => '\*italic\*',
            '**bold**'              => '\*\*bold\*\*',
            '![image](image)'       => '!\[image\]\(image\)',
            'not image](not image)' => 'not image](not image)',
            '[url](url)'            => '\[url\]\(url\)',
            '---'                   => '\-\-\-',
            '# header 1'            => '\# header 1',
            'not # header'          => 'not # header',
        ];


        foreach ($texts as $source => $result) {
            $this->assertEquals((string)$this->mock($source)->escape(), $result);
        }
    }
}
