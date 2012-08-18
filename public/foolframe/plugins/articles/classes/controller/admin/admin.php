<?php

namespace Foolframe\Plugins\Articles;

if (!defined('DOCROOT'))
	exit('No direct script access allowed');

class Controller_Plugin_Ff_Articles_Admin_Articles extends \Controller_Admin
{
	
	function structure()
	{
		return array(
			'open' => array(
				'type' => 'open',
			),
			'id' => array(
				'type' => 'hidden',
				'database' => TRUE,
				'validation_func' => function($input, $form_internal)
				{
					// check that the ID exists
					$query = \DB::select()
						->from('plugin_fs-articles')
						->where('id', $input['id'])
						->execute();
					if (count($query) != 1)
					{
						return array(
							'error_code' => 'ID_NOT_FOUND',
							'error' => __('Couldn\'t find the article with the submitted ID.'),
							'critical' => TRUE
						);
					}

					return array('success' => TRUE);
				}
			),
			'title' => array(
				'type' => 'input',
				'database' => TRUE,
				'label' => 'Title',
				'help' => __('The title of your article'),
				'class' => 'span4',
				'placeholder' => __('Required'),
				'validation' => 'trim|required'
			),
			'slug' => array(
				'database' => TRUE,
				'type' => 'input',
				'label' => __('Slug'),
				'help' => __('Insert the short name of the article to use in the url. Only alphanumeric and dashes.'),
				'placeholder' => __('Required'),
				'class' => 'span4',
				'validation' => 'required|alpha_dash',
				'validation_func' => function($input, $form_internal)
				{
					// if we're working on the same object
					if (isset($input['id']))
					{
						// existence ensured by CRITICAL in the ID check
						$result = \DB::select()
							->from('plugin_fs-articles')
							->where('id', $input['id'])
							->as_object()
							->execute()
							->current();
						
						// no change?
						if ($input['slug'] == $result->slug)
						{
							// no change
							return array('success' => TRUE);
						}
					}

					// check that there isn't already an article with that name
					$result = \DB::select()
						->from('plugin_fs-articles')
						->where('slug', $input['slug'])
						->execute();
					
					if (count($result))
					{
						return array(
							'error_code' => 'ALREADY_EXISTS',
							'error' => __('The slug is already used for another board.')
						);
					}
				}
			),
			'url' => array(
				'type' => 'input',
				'database' => TRUE,
				'class' => 'span4',
				'label' => 'URL',
				'help' => __('If you set this, the article link will actually be an outlink.'),
				'validation' => 'trim'
			),
			'article' => array(
				'type' => 'textarea',
				'database' => TRUE,
				'style' => 'height:350px; width: 90%',
				'label' => __('Article'),
				'help' => __('The content of your article, in MarkDown')
			),
			'separator-1' => array(
				'type' => 'separator'
			),
			'top' => array(
				'type' => 'checkbox',
				'database' => TRUE,
				'label' => __('Display the article link on the top of the page'),
				'help' => __('Display the article link on the top of the page')
			),
			'bottom' => array(
				'type' => 'checkbox',
				'database' => TRUE,
				'label' => __('Display the article link on the bottom of the page'),
				'help' => __('Display the article link on the bottom of the page')
			),
			'separator-2' => array(
				'type' => 'separator-short'
			),
			'submit' => array(
				'type' => 'submit',
				'class' => 'btn-primary',
				'value' => __('Submit')
			),
			'close' => array(
				'type' => 'close'
			),
		);
	}

	function manage()
	{
		$this->_views['controller_title'] = __("Articles");
		$this->_views['method_title'] = __('Manage');

		$articles = static::get_all();
		
		ob_start();
		?>

			<a href="<?php echo \Uri::create('admin/articles/edit') ?>" class="btn" style="float:right; margin:5px"><?php echo __('New article') ?></a>

			<table class="table table-bordered table-striped table-condensed">
				<thead>
					<tr>
						<th>Title</th>
						<th>Slug</th>
						<th>Edit</th>
						<th>Remove</th>
					</tr>
				</thead>
				<tbody>
					<?php 
					foreach($articles as $article) : ?>
					<tr>
						<td>
							<?php echo htmlentities($article->title) ?>
						</td>
						<td>
							<a href="<?php echo \Uri::create('_/articles/' . $article->slug) ?>" target="_blank"><?php echo $article->slug ?></a>
						</td>
						<td>
							<a href="<?php echo \Uri::create('admin/articles/edit/'.$article->slug) ?>" class="btn btn-mini btn-primary"><?php echo __('Edit') ?></a>
						</td>
						<td>
							<a href="<?php echo \Uri::create('admin/articles/remove/'.$article->id) ?>" class="btn btn-mini btn-danger"><?php echo __('Remove') ?></a>
						</td>
					</tr>
					<?php endforeach; ?>
				</tbody>
			</table>

		<?php
		$data['content'] = ob_get_clean();
		$this->_views["main_content_view"] = \View::forge('admin/plugin', $data);
		\Response::forge(\View::forge('admin/default', $this->_views));
	}


	function edit($slug = null)
	{
		$data['form'] = $this->structure();
		
		if($this->input->post())
		{
			$this->load->library('form_validation');
			$result = $this->form_validation->form_validate($data['form']);
			if (isset($result['error']))
			{
				set_notice('warning', $result['error']);
			}
			else
			{
				// it's actually fully checked, we just have to throw it in DB
				$this->save($result['success']);
				if (is_null($slug))
				{
					flash_notice('success', __('New article created!'));
					redirect('admin/articles/edit/' . $result['success']['slug']);
				}
				else if ($slug != $result['success']['slug'])
				{
					// case in which letter was changed
					flash_notice('success', __('Article information updated.'));
					redirect('admin/article/edit/' . $result['success']['slug']);
				}
				else
				{
					set_notice('success', __('Article information updated.'));
				}
			}
		}
		
		if(!is_null($slug))
		{
			$data['object'] = $this->get_by_slug($slug);
			if($data['object'] == FALSE)
			{
				show_404();
			}	
			
			$this->viewdata["function_title"] = __('Article') . ': ' . $data['object']->slug;
		}
		else 
		{
			$this->viewdata["function_title"] = __('New article') ;
		}
		
		$this->viewdata["controller_title"] = '<a href="' . Uri::create('admin/articles') . '">' . __('Articles') . '</a>';
		
		$this->viewdata["main_content_view"] = $this->load->view("admin/form_creator.php", $data, TRUE);
		$this->load->view("admin/default.php", $this->viewdata);
	}

	
	function remove($id)
	{
		if(!$article = $this->get_by_id($id))
		{
			show_404();
		}
		
		if($this->input->post())
		{
			$this->db->where('id', $id)->delete('plugin_fs-articles');
			$this->clear_cache();
			flash_notice('success', __('The article was removed'));
			redirect('admin/articles/manage');
		}
		
		$this->viewdata["controller_title"] = '<a href="' . Uri::create('admin/articles') . '">' . __('Articles') . '</a>';
		$this->viewdata["function_title"] = __('Removing article:') . ' ' . $article->title;
		$data['alert_level'] = 'warning';
		$data['message'] = __('Do you really want to remove the article?');

		$this->viewdata["main_content_view"] = $this->load->view('admin/confirm', $data, TRUE);
		$this->load->view('admin/default', $this->viewdata);
		
	}
	
}