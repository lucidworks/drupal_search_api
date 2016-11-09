<?php

namespace Drupal\search_api\Plugin\search_api\processor;

use Drupal\Core\Form\FormStateInterface;
use Drupal\search_api\Plugin\search_api\processor\Resources\Porter2;
use Drupal\search_api\Processor\FieldsProcessorPluginBase;

/**
 * Stems search terms.
 *
 * @SearchApiProcessor(
 *   id = "stemmer",
 *   label = @Translation("Stemmer"),
 *   description = @Translation("Stems search terms (e.g., <em>talking</em> to <em>talk</em>). Currently, this only acts on English language content. It uses the Porter 2 stemmer algorithm (<a href=""https://wikipedia.org/wiki/Stemming"">More information</a>). For best results, use after tokenizing."),
 *   stages = {
 *     "pre_index_save" = 0,
 *     "preprocess_index" = 0,
 *     "preprocess_query" = 0,
 *   }
 * )
 */
class Stemmer extends FieldsProcessorPluginBase {

  /**
   * Static cache for already-generated stems.
   *
   * @var string[]
   */
  protected $stems = [];

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return array(
      'exceptions' => array(
        'texan' => 'texa',
        'mexican' => 'mexic',
      ),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    $description = $this->t('If the <a href="http://snowball.tartarus.org/algorithms/english/stemmer.html">algorithm</a> does not stem words in your dataset in the desired way, you can enter specific exceptions in the form of WORD=STEM, where "WORD" is the original word in the text and "STEM" is the resulting stem. List each exception on a separate line.');

    // Convert the keyed array into a config format (word=stem)
    $default_value = http_build_query($this->configuration['exceptions'], NULL, "\n");

    $form['exceptions'] = array(
      '#type' => 'textarea',
      '#title' => $this->t('Exceptions'),
      '#description' => $description,
      '#default_value' => $default_value,
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::validateConfigurationForm($form, $form_state);

    $exceptions = $form_state->getValue('exceptions');
    if (!($parsed = parse_ini_string($exceptions))) {
      $el = $form['exceptions'];
      $form_state->setError($el, $el['#title'] . ': ' . $this->t('The entered text is not in valid WORD=STEM format.'));
    }
    else {
      $form_state->setValue('exceptions', $parsed);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function preprocessIndexItems(array $items) {
    foreach ($items as $item) {
      // Limit this processor to English language data.
      if ($item->getLanguage() !== 'en') {
        continue;
      }
      foreach ($item->getFields() as $name => $field) {
        if ($this->testField($name, $field)) {
          $this->processField($field);
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function testType($type) {
    return $this->getDataTypeHelper()->isTextType($type);
  }

  /**
   * {@inheritdoc}
   */
  protected function process(&$value) {
    // In the absence of the tokenizer processor, this ensures split words.
    $words = preg_split('/[^\p{L}\p{N}]+/u', strip_tags($value), -1, PREG_SPLIT_DELIM_CAPTURE);
    $stemmed = array();
    foreach ($words as $i => $word) {
      if ($i % 2 == 0 && strlen($word)) {
        // To optimize processing, store processed stems in a static array.
        if (!isset($this->stems[$word])) {
          $stem = new Porter2($word, $this->configuration['exceptions']);
          $this->stems[$word] = $stem->stem();
        }
        $stemmed[] = $this->stems[$word];
      }
      else {
        $stemmed[] = $word;
      }
    }
    $value = implode(' ', $stemmed);
  }

}