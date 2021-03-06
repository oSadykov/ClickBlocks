var Upload = function(el, pom)
{
  Upload.superclass.constructor.call(this, el, pom);
  
  this.init = function()
  {
    var bind = this, id = this.el.attr('id'), callback = this.el.attr('data-callback');
    var submit = function(e, data) 
    {
      var args = data.formData;
      data.formData = {'ajax-method': callback,
                       'ajax-args[]': id,
                       'ajax-key': $(document.body).attr('id'), 
                       'ajax-vs': JSON.stringify(bind.pom.getVS())};
      for (var arg in args) { data.formData['ajax-args[0][' + arg + ']'] = args[arg]; }
    };
    var always = function(e, data) 
    {
      bind.el = $('#' + id);
    };
    Upload.superclass.init.call(this);
    var ops = eval('(' + (this.el.attr('data-settings') || '{}') + ')');
    ops.dataType = 'json';
    ops.paramName = id + (this.el.attr('multiple') == 'multiple' ? '[]' : '');
    ops.multipart = true;
    this.el.fileupload(ops)
           .unbind('fileuploadsubmit', submit)
           .unbind('fileuploadalways', always)
           .bind('fileuploadalways', always);
    if (callback) this.el.bind('fileuploadsubmit', submit);
    return this;
  };
  
  this.remove = function()
  {
    this.el.fileupload('destroy');
    Upload.superclass.remove.call(this);
    return this;
  };
  
  this.value = function()
  {
    return '';
  }
};

$pom.registerControl('upload', Upload);